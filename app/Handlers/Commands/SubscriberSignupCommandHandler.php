<?php namespace App\Handlers\Commands;

use App\Events\SubscriberSignupCompleted;
use App\Models\Company;
use App\Models\CompanyBilling;
use App\Models\IncompleteSignup;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\TimeZone\TimeZone;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Discount\DiscountService;
use App\Services\Subscriptions\SubscriptionServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Services\Setup\SignupCompanyDefaultSetup;

class SubscriberSignupCommandHandler
{

    /**
     *  Command Object
     * @var App\Subscription\SubscriberSignupCommand
     */
    private $command;
    private $userRepo;
    protected $subscriptionServices;
    protected $discountService;
    protected $companyDefaultSetup;

    public function __construct(
        UserRepository $userRepo,
        SubscriptionServices $subscriptionServices,
        DiscountService $discountService,
        SignupCompanyDefaultSetup $companyDefaultSetup
    ) {
        $this->userRepo = $userRepo;
        $this->subscriptionServices = $subscriptionServices;
        $this->discountService = $discountService;
        $this->companyDefaultSetup = $companyDefaultSetup;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $this->command = $command;
        $company = null;

        DB::beginTransaction();
        try {
            // create company
            $company = Company::create($command->companyDetails);
            if(!$command->check_auth_login) {
                setScopeId($company->id);
            }
            // create company admin user
            $admin = $this->createUsers($company);

            // save trades
            $this->assignTrades($company, $command->trades);
            // save states
            $this->setStates($company, $command->states);
            // save timezone
            $this->saveTimeZone($company, $command->timezone);
            // create subscription
            $this->createSubscription($company, $command->billingDetails);
            // create recurly account`
            $this->createRecurlyAccount($company, $command->billingDetails);
            // add biling details
            $this->addBillingDetails($company, $command->billingDetails);
            // apply discount coupons
            $this->applyCoupon($company, $command->billingDetails);
            // Activate the subscription..
            $this->activation($company);
            // Delete Signup Temp Entry
            $this->deleteSignupTempEntry();
            // Add saving process of default and dependent entities
            // in the below function of SignupCompanyDefaultSetup class
            // as this class is also used when a new company created by SuperAdmin
            $this->companyDefaultSetup->setupDefaultEntities($company, $admin);
        } catch (\Exception $e) {
            Log::error('Subscriber Signup : ' . $e);
            DB::rollback();
            throw $e;
        }
        DB::commit();

        //Event..
        Event::fire('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', new SubscriberSignupCompleted($company, $command->check_auth_login));

        return $company;
    }

    private function createUsers($company)
    {

        $productId = $this->command->billingDetails['product_id'];
        //create owner-admin
        $admin = $this->userRepo->register(
            $this->command->adminDetails,
            $this->command->adminProfileDetails,
            User::GROUP_OWNER,
            $company->id,
            $departments = [],
            $productId
        );
        
        //create anonumouse user
        $this->userRepo->createAnonymous(
            $company,
            $this->command->adminDetails,
            $this->command->adminProfileDetails,
            $productId
        );
        return $admin;
    }

    private function createRecurlyAccount($company, $billingDetails)
    {

        $this->subscriptionServices->createSubscriptionAccount($company, $billingDetails['token']);
    }

    private function addBillingDetails($company, $billingDetails)
    {
        $billingDetails['company_id'] = $company->id;
        $billingDetails = CompanyBilling::create($billingDetails);
        return $billingDetails;
    }

    private function createSubscription($company, $billingDetails)
    {
        $productId = $billingDetails['product_id'];
        $applicablePlan = $company->applicableSubscriptionPlan($billingDetails['product_id']);

        $subscription = new Subscription;
        $subscription->company_id = $company->id;
        $subscription->product_id = $productId;
        $subscription->amount = $applicablePlan->amount;
        $subscription->quantity = 1;
        $subscription->setup_fee = $applicablePlan->setup_fee;
        $subscription->subscription_plan_id = $applicablePlan->id;
        $subscription->current_plan_code = $applicablePlan->code;
        $subscription->status = Subscription::INACTIVE;
        $subscription->activation_date = Carbon::now()->toDateTimeString();
        $subscription->gaf_code = ine($billingDetails, 'gaf_code') ? $billingDetails['gaf_code'] : null;
        $subscription->save();
        return $subscription;
    }

    private function applyCoupon(Company $company, $billingDetails)
    {

        if (ine($billingDetails, 'trial_coupon')) {
            $this->discountService->applyTrial($company, $billingDetails['trial_coupon']);
        }

        if (ine($billingDetails, 'setup_fee_coupon')) {
            $this->discountService->adjustSetupFee($company, $billingDetails['setup_fee_coupon']);
        }

        if (ine($billingDetails, 'monthly_fee_coupon')) {
            $this->discountService->redeemCoupon($company, $billingDetails['monthly_fee_coupon']);
        }
    }

    private function activation($company)
    {
        $subscription = $company->subscription;
        $this->subscriptionServices->subscription($subscription);
    }

    private function assignTrades($company, $tradeIds)
    {
        $otherTradeId = Trade::getOtherTradeId();

        if (!in_array($otherTradeId, $tradeIds)) {
            $tradeIds[] = $otherTradeId;
        }

        $company->trades()->attach($tradeIds);
    }

    private function setStates($company, $stateIds)
    {
        if (empty($stateIds)) {
            $stateIds[] = $company->office_state;
        }

        $company->states()->attach($stateIds);
    }

    private function saveTimeZone($company, $timezone)
    {
        try {
            if (empty($timezone)) {
                $location = $company->office_address;
                $location .= ", " . $company->office_city;
                $location .= ", " . $company->state->name;
                $location .= ", " . $company->office_zip;
                $location .= ", " . $company->country->name;
                $tz = new TimeZone($location);
                $result = $tz->get();
                if ($result->status == 'OK') {
                    $timezone = $tz->get()->timeZoneId;
                } else {
                    $timezone = 'America/New_York';
                }
            }
        } catch (\Exception $e) {
            $timezone = 'America/New_York';
        }

        Setting::create([
            'name' => 'Time Zone',
            'key' => 'TIME_ZONE',
            'value' => $timezone,
            'company_id' => $company->id
        ]);
    }

    private function deleteSignupTempEntry()
    {
        try {
            if ($this->command->signup_temp_token) {
                IncompleteSignup::whereToken($this->command->signup_temp_token)->delete();
            }
        } catch (\Exception $e) {
            //
        }
    }
}
