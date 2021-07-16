<?php

namespace App\Services\Recurly;

use App\Events\SubscriberReactivated;
use App\Events\SubscriberSuspended;
use App\Models\Company;
use App\Models\CompanyBilling;
use App\Models\RedeemedCoupon;
use App\Models\SetupAction;
use App\Models\State;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Subscriptions\SubscriptionServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Recurly_BillingInfo;
use App\Events\Twilio\ReleaseTwilioNumberForAllUsers;

class RecurlyNotificationsHandler
{

    protected $subscriptionServices;

    public function __construct(SubscriptionServices $subscriptionServices)
    {

        $this->subscriptionServices = $subscriptionServices;
    }

    public function getNotifiacation()
    {
        $post_xml = file_get_contents("php://input");
        $notification = new \Recurly_PushNotification($post_xml);
        Log::info($notification->type);

        // handle recurly notification..
        switch ($notification->type) {
            case "new_subscription_notification":
                $this->newSubscription($notification);
                break;

            case "expired_subscription_notification":
                $this->subscriptionExpired($notification);
                break;

            case "canceled_subscription_notification":
                $this->subscriptionCanceled($notification);
                break;

            case "reactivated_account_notification":
                $this->subscriptionReactive($notification);
                break;

            case "renewed_subscription_notification":
                $this->subscriptionRenewed($notification);
                break;

            case "billing_info_updated_notification":
                $this->billingInfoUpdated($notification);
                break;

            case "successful_payment_notification":
                $this->billingSuccessfull($notification);
                break;

            case "failed_payment_notification":
                $this->billingFailed($notification);
                break;

            case 'updated_subscription_notification':
                $this->subscriptionUpdated($notification);
                break;

            case 'updated_account_notification':
                $this->accountUpdated($notification);
                break;

            case 'canceled_account_notification':
                # code...
                break;

            case 'subscription_paused_notification':
                # code...
                break;

            case 'subscription_resumed_notification':
                # code...
                break;

            /* add more notifications to process */
        }
    }

    /********************* Private function **********************/

    private function newSubscription($notification)
    {

        $accountCode = $notification->account->account_code;
        $company = Company::where('recurly_account_code', $accountCode)->first();

        if (!$company) {
            return;
        }

        $newSubscription = $notification->subscription;
        $subscription = $company->subscription;
        if (!$subscription) {
            return;
        }

        switch ($subscription->status) {
            case Subscription::TRIAL:
                $this->startSubscriptionAfterTrial($subscription, $newSubscription);
                break;

            case Subscription::SUSPENDED:
            case Subscription::MANUALLY_SUSPENDED:
            case Subscription::UNSUBSCRIBED:
                $this->subscriptionUpdated($notification, true);
                break;

            default:
                # code...
                break;
        }
    }

    // handle canceled subscription notification..
    private function subscriptionCanceled($notification)
    {
        $uuid = $notification->subscription->uuid;
        $subscription = $this->getSubscriptionByUUID($uuid);
        // suspended the company account.
        if (!$subscription) {
            return;
        }
        if ($subscription->status == Subscription::ACTIVE) {
            $subscription->status = Subscription::SUSPENDED;
            $subscription->save();

            Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForAllUsers', new ReleaseTwilioNumberForAllUsers($company));
        }
    }

    // handle expired subscription notification..
    private function subscriptionExpired($notification)
    {
        $uuid = $notification->subscription->uuid;
        $subscription = $this->getSubscriptionByUUID($uuid);
        // suspended the company account.
        if (!$subscription) {
            return;
        }
        if ($subscription->status == Subscription::ACTIVE) {
            $subscription->status = Subscription::SUSPENDED;
        }
        $subscription->recurly_uuid = null;
        $subscription->save();
        $company = $subscription->company;
        Event::fire('JobProgress.Subscriptions.Events.SubscriberSuspended', new SubscriberSuspended($company));
        Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForAllUsers', new ReleaseTwilioNumberForAllUsers($company));
    }

    // handle closed invoce notifiaction..
    private function subscriptionReactive($notification)
    {
        $uuid = $notification->subscription->uuid;
        $subscription = $this->getSubscriptionByUUID($uuid);
        // Activate the company account..
        if (!$subscription) {
            return;
        }
        $subscription->status = Subscription::ACTIVE;
        $subscription->save();
    }

    // handle billing info updated notification..
    private function billingInfoUpdated($notification)
    {
        // Log::info(json_encode($notification));
        $accountCode = $notification->account->account_code;
        $company = Company::where('recurly_account_code', $accountCode)->first();
        if (!$company) {
            return;
        }
        $subscription = $company->subscription;
        if (!$subscription) {
            return;
        }
        if (empty($subscription->recurly_uuid) && ($subscription->status == Subscription::SUSPENDED)) {
            $this->subscriptionServices->subscription($subscription, false);
            Event::fire('JobProgress.Subscriptions.Events.SubscriberReactivated', new SubscriberReactivated($company));
        }

        $this->updateBillingDetails($company);
    }

    // handle billing successful notification..
    private function billingSuccessfull($notification)
    {
        $accountCode = $notification->account->account_code;
        $company = Company::where('recurly_account_code', $accountCode)->first();
        if (!$company) {
            return;
        }
        $subscription = $company->subscription;
        if (!$subscription) {
            return;
        }
        $subscription->last_transaction_date = Carbon::now()->toDateTimeString();
        $subscription->last_transaction_status = 'successful';
        $subscription->save();
    }

    // handle billing failed notification..
    private function billingFailed($notification)
    {
        $accountCode = $notification->account->account_code;
        $company = Company::where('recurly_account_code', $accountCode)->first();
        if (!$company) {
            return;
        }
        $subscription = $company->subscription;
        if (!$subscription) {
            return;
        }
        $subscription->last_transaction_date = Carbon::now()->toDateTimeString();
        $subscription->last_transaction_status = 'failed';
        $subscription->save();
    }

    // change subscription status according
    private function getSubscriptionByUUID($uuid)
    {
        $subscription = Subscription::where('recurly_uuid', $uuid)->first();
        if (!$subscription) {
            return false;
        }
        return $subscription;
    }

    // handle susbcription renewal notifiaction..
    private function subscriptionRenewed($notification)
    {
        $cycleChange = true;
        $renewedSubscription = $notification->subscription;
        $uuid = $renewedSubscription->uuid;
        $subscription = $this->getSubscriptionByUUID($uuid);

        if (!$subscription) {
            return;
        }

        $subscription->next_renewal_date = $renewedSubscription->current_period_ends_at;

        if (is_null($subscription->current_cycle_started_at)) {
            $subscription->current_cycle_started_at = $renewedSubscription->current_period_started_at;
        } else {
            $newCycleDate = (new Carbon($renewedSubscription->current_period_started_at))->toDateString();
            $currentCycleDate = (new Carbon($subscription->current_cycle_started_at))->toDateString();
            if ($newCycleDate != $currentCycleDate) {
                $subscription->current_cycle_started_at = $renewedSubscription->current_period_started_at;
            } else {
                $cycleChange = false;
            }
        }

        if ($subscription->next_renewal_plan == $renewedSubscription->plan->plan_code) {
            $subscription->next_renewal_plan = null;
        }
        if (($cycleChange == true) && ($subscription->remaining_cycles != 'unlimited') && ($subscription->remaining_cycles != '0')) {
            $subscription->remaining_cycles = (int)$subscription->remaining_cycles - 1;
        }

        $subscription->save();
        if ($subscription->remaining_cycles == '0') {
            $this->subscriptionServices->checkForNextUpdation($subscription->company);
        }
    }

    // handle subscription changes from recurly
    private function subscriptionUpdated($notification, $reactivated = false)
    {
        $accountCode = $notification->account->account_code;
        $updatedSubscription = $notification->subscription;
        $uuid = $notification->subscription->uuid;

        $company = Company::where('recurly_account_code', $accountCode)->first();
        if (!$company) {
            return;
        }

        $subscription = $company->subscription;

        if (!$subscription) {
            return;
        }
        $currentPlan = SubscriptionPlan::whereCode($subscription->current_plan_code)->first();

        if (!$currentPlan) {
            return;
        }

        $amount = ($updatedSubscription->total_amount_in_cents / $updatedSubscription->quantity) / 100;

        if ($currentPlan->amount != $amount) {
            $subscription->amount = $amount;
        }

        $updatedPlan = $updatedSubscription->plan;
        if (!($subscription->recurly_uuid) || ($currentPlan->code != $updatedPlan->plan_code)) {
            $updatedPlan = SubscriptionPlan::whereCode($updatedPlan->plan_code)->first();
            if ($updatedPlan) {
                $subscription->recurly_uuid = $uuid;
                $subscription->product_id = $updatedPlan->product_id;
                $subscription->subscription_plan_id = $updatedPlan->id;
                $subscription->next_renewal_plan = null;
                $subscription->current_plan_code = $updatedPlan->code;
                $subscription->remaining_cycles = 'unlimited';
                $subscription->amount = $amount;
            }

            if (!is_null($subscription->redeemed_monthly_fee_coupon)) {
                $monthlyFeeCoupon = RedeemedCoupon::whereCompanyId($subscription->company_id)->whereValidFor('monthly_fee')->whereIsActive(true)->update(['is_active' => false]);

                $subscription->redeemed_monthly_fee_coupon = null;
            }
        }

        if ($subscription->status != Subscription::ACTIVE) {
            $subscription->status = Subscription::ACTIVE;
        }

        $subscription->current_cycle_started_at = $updatedSubscription->current_period_started_at;
        $subscription->next_renewal_date = $updatedSubscription->current_period_ends_at;

        $subscription->save();

        if ($reactivated) {
            $this->subscriptionServices->checkForNextUpdation($company);
        }

        return;
    }

    private function accountUpdated($notification)
    {
        $account = $notification->account;

        $company = Company::where('recurly_account_code', $account->account_code)->first();
        if (!$company) {
            return;
        }

        $company->name = $account->company_name;
        $company->save();

        $subscriber = $company->subscriber;
        $subscriber->first_name = $account->first_name;
        $subscriber->last_name = $account->last_name;
        $subscriber->save();

        return;
    }

    private function updateBillingDetails($company)
    {
        try {
            $companyBilling = $company->billing;

            $billing_info = Recurly_BillingInfo::get($company->recurly_account_code);
            // $cardData = [
            //     'number' => $billing_info->first_six . '******' . $billing_info->last_four,
            //     'year' => $billing_info->year,
            //     'month' => $billing_info->month,
            //     'type' => $billing_info->card_type,
            // ];
            $state = State::whereCode($billing_info->state)->first();

            $data = [
                // 'card_details' => $cardData,
                'company_id' => $company->id,
                'address' => $billing_info->address1,
                'address_line_1' => $billing_info->address2,
                'city' => $billing_info->city,
                'zip' => $billing_info->zip,
                'state_id' => $state->id,
                'country_id' => $state->country_id,
                'email' => $company->office_email,
            ];

            if (!$companyBilling) {
                $billingDetails = CompanyBilling::create($data);

                $actionsCompleted = $company->setupActions()->pluck('setup_action_id')->toArray();

                //get product id from company's Subscription Model..
                $productId = $company->subscription->product_id;

                //get action from SetupAction Model..
                $action = SetupAction::productId($productId)->ActionName(SetupAction::BILLING_DETAILS)->first();

                if (!in_array($action->id, $actionsCompleted)) {
                    $company->setupActions()->attach([$action->id]);
                }

                return true;
            } else {
                $companyBilling->update($data);
                return true;
            }
        } catch (\Recurly_NotFoundError $e) {
            // Could not find account or account
            // doesn't have billing info
            // print "Not found: $e";
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function startSubscriptionAfterTrial($subscription, $newSubscription)
    {
        $subscription->status = Subscription::ACTIVE;
        $subscription->current_cycle_started_at = $newSubscription->current_period_started_at;
        $subscription->next_renewal_date = $newSubscription->current_period_ends_at;

        if (($subscription->remaining_cycles != 'unlimited') && ($subscription->remaining_cycles != '0')) {
            $subscription->remaining_cycles = (int)$subscription->remaining_cycles - 1;
        }

        $subscription->save();

        if ($subscription->remaining_cycles == '0') {
            $this->subscriptionServices->checkForNextUpdation($subscription->company);
        }
    }
}
