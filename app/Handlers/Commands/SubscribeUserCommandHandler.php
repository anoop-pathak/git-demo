<?php namespace App\Handlers\Commands;

use App\Events\SubscriberWasCreated;
use App\Events\UserWasCreated;
use App\Models\Company;
use App\Models\CompanyNote;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subscriptions\SubscriptionServices;
use App\Services\TimeZone\TimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;
use App\Services\Setup\SignupCompanyDefaultSetup;

class SubscribeUserCommandHandler
{

    /**
     *  Command Object
     * @var App\Subscription\SubscribeUserCommand
     */
    private $command;

    protected $userRepo;

    protected $subscriptionServices;
    protected $companyDefaultSetup;

    public function __construct(UserRepository $userRepo, SubscriptionServices $subscriptionServices, SignupCompanyDefaultSetup $companyDefaultSetup)
    {
        $this->userRepo = $userRepo;
        $this->subscriptionServices = $subscriptionServices;
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
            $company = Company::create($command->companyData);
            $this->addNote($company);
            $this->saveTimeZone($company, $command->timezone);
            $admin = $this->createUsers($company);
            $this->saveSubscriptionDetails($company, $command->productId);
            $this->createRecurlyAccount($company);

            // Add saving process of default and dependent entities
            // in the below function of SignupCompanyDefaultSetup class
            // as this class is also used when a new company singed up
            $this->companyDefaultSetup->setupDefaultEntities($company, $admin);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        Event::fire('JobProgress.Subscriptions.Events.SubscriberWasCreated', new SubscriberWasCreated($company));
        return $company;
    }

    /**
     * @TODO 1.)Create constants for GROUPS
     * 2.) Move User Creation process to Models
     */
    private function createUsers($company)
    {

        $productId = $this->command->productId;
        //create owner-admin
        $admin = $this->userRepo->register(
            $this->command->userData,
            $this->command->userProfileData,
            User::GROUP_OWNER,
            $company->id,
            $departments = [],
            $productId
        );

        //create anonumouse user
        $this->userRepo->createAnonymous(
            $company,
            $this->command->userData,
            $this->command->userProfileData,
            $productId
        );
        Event::fire('JobProgress.Users.Events.UserWasCreated', new UserWasCreated($admin));
        return $admin;
    }

    private function addNote($company)
    {

        if ($this->command->note) {
            $companyNote = new CompanyNote();
            $companyNote->company_id = $company->id;
            $companyNote->note = $this->command->note;
            $companyNote->save();
        }
    }

    private function saveSubscriptionDetails($company, $productId)
    {
        $product = Product::findOrFail($productId);
        $applicablePlan = $company->applicableSubscriptionPlan($productId);

        $subscription = new Subscription();
        $subscription->company_id = $company->id;
        $subscription->product_id = $productId;
        $subscription->amount = $applicablePlan->amount;
        $subscription->quantity = $company->users()->billable()->count();
        $subscription->setup_fee = $applicablePlan->setup_fee;
        $subscription->subscription_plan_id = $applicablePlan->id;
        $subscription->current_plan_code = $applicablePlan->code;
        $subscription->status = Subscription::INACTIVE;
        $subscription->status_updated_at = Carbon::now()->toDateTimeString();
        $subscription->save();
        return true;
    }

    private function createRecurlyAccount($company)
    {

        $this->subscriptionServices->createSubscriptionAccount($company);
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
}
