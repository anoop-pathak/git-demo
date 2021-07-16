<?php namespace App\Services\Subscriptions;

use App\Events\SubscriberManuallySuspended;
use App\Events\SubscriberReactivated;
use App\Events\SubscriberTerminated;
use App\Events\SubscriberUnsubscribed;
use App\Models\Company;
use App\Models\CompanyBilling;
use App\Models\Product;
use App\Models\RedeemedCoupon;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Recurly\Recurly;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Events\ReleaseTwilioNumberForAllUsers;

class SubscriptionServices
{

    protected $recurly;

    public function __construct(Recurly $recurly)
    {

        $this->recurly = $recurly;
    }

    public function createSubscriptionAccount(Company $company, $recurlytoken = null)
    {

        $accountCode = clean(strtolower($company->name)) .'_'.$company->id;
        $accountDetails['account_code'] = $accountCode;
        $accountDetails['company_name'] = $company->name;

        $subscriber = $company->subscriber;
        $accountDetails['first_name'] = $subscriber->first_name;
        $accountDetails['last_name'] = $subscriber->last_name;
        $accountDetails['email'] = $subscriber->email;
        $this->recurly->createAccount($accountDetails, $recurlytoken);
        $company->recurly_account_code = $accountCode;
        $company->save();
    }

    public function updateSubscriptionAccount(User $subscriber)
    {
        $accountCode = $subscriber->company->recurly_account_code;
        $accountDetails['first_name'] = $subscriber->first_name;
        $accountDetails['last_name'] = $subscriber->last_name;
        $accountDetails['email'] = $subscriber->email;
        $this->recurly->updateAccountDetails($accountCode, $accountDetails);
    }

    public function addBillingDetails($billingDetails)
    {
        $recurlyBillingInfo = $this->recurly->addBillingInfo($billingDetails['recurly_account_code'], $billingDetails['token']);
        $companyBilling = CompanyBilling::where('company_id', $billingDetails['company_id'])->first();
        if (!$companyBilling) {
            $billingDetails = CompanyBilling::create($billingDetails);
        } else {
            $companyBilling->update($billingDetails);
            return $companyBilling;
        }
        return $billingDetails;
    }

    public function updateBillingDetails($billingDetails)
    {
        DB::beginTransaction();
        try {
            $companyBilling = CompanyBilling::where('company_id', $billingDetails['company_id'])->firstOrFail();
            $companyBilling->update($billingDetails);

            $billingAddressInfo = [];
            $billingAddressInfo['account_code'] = $billingDetails['recurly_account_code'];
            $billingAddressInfo['address1'] = $companyBilling->address;
            $billingAddressInfo['address2'] = $companyBilling->address_line_1;
            $billingAddressInfo['city'] = $companyBilling->city;
            $billingAddressInfo['state'] = $companyBilling->state->name;
            $billingAddressInfo['country'] = $companyBilling->country->name;
            $billingAddressInfo['zip'] = $companyBilling->zip;

            $recurlyBillingInfo = $this->recurly->updateBillingInfo($billingAddressInfo);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $companyBilling;
    }

    public function subscription(Subscription $subscription, $setup_fee = true)
    {

        $company = $subscription->company;

        $productId = $subscription->product_id;

        $applicablePlan = $subscription->plan()->first();

        $accountCode = $company->recurly_account_code;

        // add charges..
        if ($setup_fee) {
            if (!$this->isSetupFeeCharged($accountCode)) {
                $this->addCharges($subscription, $accountCode);
            }
        }

        $users_count = $company->users()->billable()->count();
        // $subs_count = $company->subcontractors()->active()->count();

        $subscriberData['account_code'] = $accountCode;
        $subscriberData['plan_code'] = $applicablePlan->code;
        $subscriberData['currency'] = \config('jp.currency');
        $subscriberData['users_quantity'] = $users_count;

        // check for trial..
        $startDate = null;
        if ($startDate = $this->checkTrialCoupon($company)) {
            $subscriberData['starts_at'] = $startDate;
        }

        // if( $subs_count ) {
        //     $subscriberData['add_on_code'] = 'sub';
        //     $subscriberData['addon_quantity'] = $subs_count;
        // }

        $recurlySubscription = $this->recurly->subscription($subscriberData);

        if ($startDate) {
            $subscription->status = Subscription::TRIAL;
        } else {
            $subscription->status = Subscription::ACTIVE;
        }

        $subscription->recurly_uuid = $recurlySubscription->uuid;

        $subscription->current_cycle_started_at = $recurlySubscription->current_period_started_at;

        $subscription->next_renewal_date = $recurlySubscription->current_period_ends_at;

        $subscription->status_updated_at = Carbon::now()->toDateTimeString();

        if (($subscription->remaining_cycles != 'unlimited')
            && ($subscription->remaining_cycles != '0')
            && ($subscription->status == Subscription::ACTIVE)) {
            $subscription->remaining_cycles = (int)$subscription->remaining_cycles - 1;
        }

        $subscription->save();

        if ($subscription->remaining_cycles == '0') {
            $plan = $subscription->company->applicableSubscriptionPlan($subscription->product_id);
            $this->updateSubscription($subscription, $recurlySubscription, $plan);
        }

        $this->manageAttachedCoupons($subscription);
    }

    /**
     * This function check subscription plan's user limit when new user is added..
     */
    public function checkForNextUpdation(Company $company, $recurlySubscription = null)
    {

        //check company's subscription. If not subscribed yet return false..
        if (!$subscription = $company->subscription) {
            return;
        }

        //if company subscription activated then then check applicable plan..
        $activePlanId = $subscription->plan->id;
        $activeProductId = $subscription->plan->product_id;
        if ($subscription->remaining_cycles == '0') {
            $applicablePlan = $company->applicableSubscriptionPlan($activeProductId);
        } else {
            $applicablePlan = $subscription->plan;
        }

        $this->updatePlanAndAmount($company, $applicablePlan);

        $recurly_uuid = $subscription->recurly_uuid;
        if (empty($recurly_uuid)) {
            return;
        }

        //change plan if required due to limit of number of users..
        if ($activePlanId != $applicablePlan->id) {
            $this->updateSubscription($subscription, $recurlySubscription, $applicablePlan);
        } else {
            //change quantity..
            $this->updateSubscription($subscription, null, null, true);
        }
    }

    public function updateSubscription(Subscription $subscription, $recurlySubscription = null, $applicablePlan = null, $immediately = false)
    {
        $company = $subscription->company;
        $subscriptionData['uuid'] = $subscription->recurly_uuid;
        $subscriptionData['next_renewal_plan'] = $subscription->next_renewal_plan;
        if ($applicablePlan) {
            $subscriptionData['plan_code'] = $applicablePlan->code;
        }
        $subscriptionData['users_quantity'] = $company->users()->billable()->count();

        $subs_count = $company->primeSubcontractors()->active()->count();
        $subscriptionData['add_on_code'] = 'sub';
        $subscriptionData['addon_quantity'] = $subs_count;

        $updatedSubscription = $this->recurly->upgradeSubscription($subscriptionData, $recurlySubscription, $immediately);
        $subscription->recurly_uuid = $updatedSubscription->uuid;
        $subscription->next_renewal_date = $updatedSubscription->current_period_ends_at;
        if ($subscription->remaining_cycles == '0') {
            $this->manageAttachedCoupons($subscription);
            $subscription->coupon_valid_till = null;
            $subscription->redeemed_monthly_fee_coupon = null;
            $subscription->remaining_cycles = 'unlimited';
            $subscription->next_renewal_plan = $applicablePlan->code;
        }
        if ($applicablePlan) {
            $subscription->subscription_plan_id = $applicablePlan->id;
            // $subscription->amount = $applicablePlan->amount;
        }
        $subscription->save();
    }

    /**
     * Manualy Suspend a subscription by super-admin..
     * @param  Subscription $subscription | Subscription object
     * @return bool
     */
    public function suspend(Subscription $subscription)
    {
        $this->recurly->cancelSubscription($subscription->recurly_uuid);
        // if subscription is in trial period remove subsription..
        if ($subscription->status == Subscription::TRIAL) {
            $subscription->recurly_uuid = null;
        }

        $subscription->status = Subscription::MANUALLY_SUSPENDED;
        $subscription->status_updated_at = Carbon::now()->toDateTimeString();
        $subscription->save();
        $company = $subscription->company;
        Event::fire('JobProgress.Subscriptions.Events.SubscriberManuallySuspended', new SubscriberManuallySuspended($company));
        Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForAllUsers', new ReleaseTwilioNumberForAllUsers($company));

        return true;
    }

    /**
     * Manualy Suspend a subscription by super-admin..
     * @param  Subscription $subscription | Subscription object
     * @return bool
     */
    public function unsubscribe(Subscription $subscription)
    {
        $this->recurly->cancelSubscription($subscription->recurly_uuid);
        $subscription->status = Subscription::UNSUBSCRIBED;

        // if subscription is in trial period remove subsription..
        if ($subscription->status == Subscription::TRIAL) {
            $subscription->recurly_uuid = null;
        }

        $subscription->status_updated_at = Carbon::now()->toDateTimeString();
        $subscription->save();
        $company = $subscription->company;
        Event::fire('JobProgress.Subscriptions.Events.SubscriberUnsubscribed', new SubscriberUnsubscribed($company));
        Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForAllUsers', new ReleaseTwilioNumberForAllUsers($company));
        return true;
    }

    /**
     * Re-activate subscription by super-admin
     * @param  Subscription $subscription | Subscription object
     * @return bool
     */
    public function reactivate(Subscription $subscription)
    {
        if ($subscription->recurly_uuid) {
            $this->recurly->reactivateSubscription($subscription->recurly_uuid);
        } else {
            $this->subscription($subscription, false);
        }

        $subscription->status = Subscription::ACTIVE;
        $subscription->status_updated_at = Carbon::now()->toDateTimeString();
        $subscription->save();

        $company = $subscription->company;
        Event::fire('JobProgress.Subscriptions.Events.SubscriberReactivated', new SubscriberReactivated($company));
        return true;
    }

    /**
     * Close Subscription account
     * @param  Subscription $subscription | Subscription object
     * @return bool
     */
    public function terminate(Subscription $subscription)
    {
        $recurlyAccountCode = $subscription->company->recurly_account_code;
        $this->recurly->closeAccount($recurlyAccountCode);
        $subscription->status = Subscription::TERMINATED;
        $subscription->status_updated_at = Carbon::now()->toDateTimeString();
        $subscription->save();
        $company = $subscription->company;
        Event::fire('JobProgress.Subscriptions.Events.SubscriberTerminated', new SubscriberTerminated($company));
        return true;
    }

    /********************** Private function ***********************/

    private function updatePlanAndAmount(Company $company, $plan)
    {
        $subscription = $company->subscription;
        $subscription->subscription_plan_id = $plan->id;
        if ($plan->product_id != Product::PRODUCT_JOBPROGRESS_PRO) {
            // $subscription->amount = $plan->amount;
            $subscription->quantity = $company->users()->billable()->count();
        }
        $subscription->save();
    }

    /**
     * Add Charges to subscription.
     * @param object $plan | Object of Plans Model
     * @param string $recurlyAccountCode | Recurly Account Code
     */
    private function addCharges(Subscription $subscription, $recurlyAccountCode)
    {

        if (!$subscription->setup_fee) {
            return; // if setup fee is null then return.
        }
        $this->recurly->addCharges(
            $accountCode = $recurlyAccountCode,
            $amount = $subscription->setup_fee,
            $description = 'Setup fee',
            $currency = \config('jp.currency'),
            $acaccountingCode = Subscription::SETUP_FEE
        );
        return true;
    }

    private function manageAttachedCoupons($subscription)
    {
        // // remove setup_fee copon
        // $setupFeeCoupon = RedeemedCoupon::whereCompanyId($subscription->company_id)->whereValidFor('setup_fee')->whereIsActive(true)->first();
        // if($setupFeeCoupon) {
        //     $setupFeeCoupon->is_active = false;
        //     $setupFeeCoupon->save();
        // }

        //remove monthly coupon..
        if (is_null($subscription->redeemed_monthly_fee_coupon)) {
            return;
        }
        $monthlyFeeCoupon = RedeemedCoupon::whereCompanyId($subscription->company_id)->whereValidFor('monthly_fee')->whereIsActive(true)->first();

        if (is_null($monthlyFeeCoupon)) {
            return;
        }

        if ($subscription->remaining_cycles == '0') {
            $monthlyFeeCoupon->is_active = false;
        }

        if (is_null($monthlyFeeCoupon->start_date)) {
            $monthlyFeeCoupon->start_date = Carbon::now();
        }

        if (is_null($monthlyFeeCoupon->end_date)) {
            $monthlyFeeCoupon->end_date = $subscription->coupon_valid_till;
        }

        $monthlyFeeCoupon->save();
    }

    private function isSetupFeeCharged($accountCode)
    {
        $charges = $this->recurly->getAddedChargesList($accountCode);
        foreach ($charges as $charge) {
            if (($charge->accounting_code == Subscription::SETUP_FEE) || ($charge->description == 'Setup fee')) {
                return true;
            }
        }
        return false;
    }

    private function checkTrialCoupon($company)
    {
        $redeemedTrialCoupon = $company->redeemedCoupons()->trial()->active()->first();

        if (!$redeemedTrialCoupon) {
            return null;
        }

        $coupon = $redeemedTrialCoupon->coupon;

        $trialPeriod = $coupon->temporal_amount;
        $currentDateTime = Carbon::now();
        $currentDateTime->addDays($trialPeriod);

        $subscriptionStartDate = $currentDateTime->toDateTimeString();

        $redeemedTrialCoupon->update(['is_active' => false]);

        return $subscriptionStartDate;
    }
}
