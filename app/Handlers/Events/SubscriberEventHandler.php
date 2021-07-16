<?php

namespace App\Handlers\Events;

use App\Models\ActivityLog;
use App\Models\User;
use App\Repositories\WorkflowRepository;
use ActivityLogs;
use Firebase;
use App\Services\MobileMessages\MobileMessageService;
use App\Services\Recurly\Recurly;
use App\Services\Setup\CompanySetup;
use App\Transformers\CompaniesTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriberEventHandler
{

    protected $workflowRepo;

    protected $companySetup;

    public function __construct(WorkflowRepository $repo, CompanySetup $companySetup, Recurly $recurly)
    {

        $this->workflowRepo = $repo;
        $this->companySetup = $companySetup;
        $this->recurly = $recurly;
    }

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('JobProgress.Subscriptions.Events.SubscriberWasCreated', 'App\Handlers\Events\SubscriberEventHandler@companyBasicSetup', 1);
        $event->listen('JobProgress.Subscriptions.Events.SubscriberWasCreated', 'App\Handlers\Events\SubscriberEventHandler@setpDefaultWorkflow');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@sendWelcomeMail');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@sendActivationMailToUsers');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@maintainActivityLog');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@createSupportAccount');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@sendSMS');
        $event->listen('JobProgress.Subscriptions.Events.SubscriptionCompleted', 'App\Handlers\Events\SubscriberEventHandler@createSampleData');

        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@companyBasicSetup', 1);
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@setpDefaultWorkflow');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@sendWelcomeMail');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@maintainActivityLog');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@createSupportAccount');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@createSampleData');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@firebaseSetup');

        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@sendSMS');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSignupCompleted', 'App\Handlers\Events\SubscriberEventHandler@createMeasurementAttributes');

        $event->listen('JobProgress.Subscriptions.Events.SubscriberAccountUpdated', 'App\Handlers\Events\SubscriberEventHandler@updateRecurlyAccount');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberAccountUpdated', 'App\Handlers\Events\SubscriberEventHandler@updateAnonymous');
    }


    public function setpDefaultWorkflow($event)
    {
        $company = $event->company;
        $subscription = $company->subscription;
        $userId = 0;

        if(isset($event->checkAuthLogin) && $event->checkAuthLogin) {
            $user = \Auth::user();
            if ($user) {
                $userId = $user->id;
            }
        }

        $this->workflowRepo->setupDefault($company->id, $userId, $subscription->product_id);
    }

    public function companyBasicSetup($event)
    {

        $this->companySetup->run($event->company);
    }

    public function sendWelcomeMail($event)
    {
        try {
            $company = $event->company;
            $data = $company->subscriber->toArray();
            $data['company_id'] = $company->id;
            $data['company_name'] = $company->name;
            $data['company_email'] = $company->office_email;
            $data['users'] = $company->users->count();
            $data['plan'] = $company->subscription->plan->title;

            // mail to subscriber..
            Mail::send('emails.users.welcome', $data, function ($message) use ($data) {
                if ($data['company_email'] == $data['email']) {
                    $message->to($data['email'])->subject(\Lang::get('response.events.email_subjects.welcome'));
                } else {
                    $message->to($data['email'])
                        ->cc($data['company_email'])
                        ->subject(\Lang::get('response.events.email_subjects.welcome'));
                }
            });

            // mail to superadmin..
            $superAdmin = User::superAdmin()->toArray();
            Mail::send('emails.users.super_admin', $data, function ($message) use ($superAdmin) {
                $message->to($superAdmin['email'])->subject(\Lang::get('response.events.email_subjects.new_subscription'));
            });
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function sendActivationMailToUsers($event)
    {
        try {
            $company = $event->company;
            $users = $company->users;

            foreach ($users as $user) {
                $data = $user->toArray();
                Mail::send('emails.users.activation', $data, function ($message) use ($data) {
                    $message->to($data['email'])->subject(\Lang::get('response.events.email_subjects.activation_completed'));
                });
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function maintainActivityLog($event)
    {
        try {
            $company = $event->company;
            $transform = App::make('Sorskod\Larasponse\Larasponse');
            $displayData = $transform->item($company, new CompaniesTransformer);

            // maintain activity log for superadmin..
            ActivityLogs::maintain(
                ActivityLog::FOR_SUPERADMIN,
                ActivityLog::NEW_SUBSCRIPTION,
                $displayData
            );
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function createSupportAccount($event)
    {
        try {
            $company = $event->company;
            $user = $company->subscriber;


            if (is_null($company->zendesk_id)) {
                $service = App::make(\App\Zendesk\ZendeskService::class);
                $organization = $service->addOrganization($company); //create organization on zendesk.
                //attach organization id to company data.
                $company->zendesk_id = $organization ? $organization->id : null;
                $company->zendesk_id = $organization->id;
                $company->save();
            }

            if (is_null($user->zendesk_id)) {
                $service = App::make(\App\Zendesk\ZendeskService::class);
                $zendeskUser = $service->addUser($user, $organization->id);// create user on zendesk
                //attach user id to user data.
                $user->zendesk_id = $zendeskUser ? $zendeskUser->id : null;
                $user->save();
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Update anonymous user
     */
    public function updateAnonymous($event)
    {
        $company = $event->company;
        $subscriber = $company->subscriber;
        $anonymous = $company->anonymous;
        if ($anonymous) {
            $anonymous->email = ucfirst(substr(clean($company->name), 0, 1)) . strtolower(clean($subscriber->last_name)) . $company->id . '@jobprogress.com';
            $anonymous->password = 'JP' . strtolower(strrev(str_replace(' ', '', $subscriber->last_name)));
            $anonymous->save();
        }
    }

    /**
     * Firebase Setup
     * @param  object $event
     * @return Void
     */
    public function firebaseSetup($event)
    {
        $company = $event->company;

        Firebase::companyDatabaseSetup($company->id);
    }

    /**
     * Send SMS
     * @param  $event
     * @return Void
     */
    public function sendSMS($event)
    {
        try {
            $company = $event->company;
            $countryCode = $company->country->code;
            $phoneNumber = $company->office_phone;
            $message = new  MobileMessageService;
            $message->send(
                $phoneNumber,
                config('mobile-message.contents'),
                $countryCode,
                $mediaUrls = [],
                $useQueue = true
            );
        } catch (\Exception $e) {
            //exception handle
        }
    }

    /*
     * Create company sample customer
     * @param  object $event 
     * @return Void
     */
    public function createSampleData($event)
    {
        $company = $event->company;

        $this->companySetup->createSampleData($company);
    }

    public function updateRecurlyAccount($event)
    {
        try {
            $company = $event->company;
            $accountCode = $company->recurly_account_code;
            $accountDetails['company_name'] = $company->name;
            $this->recurly->updateAccountDetails($accountCode, $accountDetails);
        } catch (\Exception $e) {
            //no action..
        }
    }

    public function createMeasurementAttributes($event)
    {
        try {
            $company =  $event->company;
            $app = \App::make('App\Services\Measurement\MeasurementAttributeService');
            $app->createNewAttributes($company);
        } catch (\Exception $e) {
            \Log::error($e);
        }
    }
}
