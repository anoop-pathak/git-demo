<?php

namespace App\Handlers\Events;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use DripCampaign;

class SubscriptionEventHandler
{

    public function subscribe($event)
    {
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSuspended', 'App\Handlers\Events\SubscriptionEventHandler@suspended');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberSuspended', 'App\Handlers\Events\SubscriptionEventHandler@cancelAllDripCampaign');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberManuallySuspended', 'App\Handlers\Events\SubscriptionEventHandler@manually_suspended');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberManuallySuspended', 'App\Handlers\Events\SubscriptionEventHandler@cancelAllDripCampaign');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberUnsubscribed', 'App\Handlers\Events\SubscriptionEventHandler@unsubscribed');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberUnsubscribed', 'App\Handlers\Events\SubscriptionEventHandler@cancelAllDripCampaign');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberReactivated', 'App\Handlers\Events\SubscriptionEventHandler@reactivated');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberTerminated', 'App\Handlers\Events\SubscriptionEventHandler@terminated');
        $event->listen('JobProgress.Subscriptions.Events.SubscriberTerminated', 'App\Handlers\Events\SubscriptionEventHandler@cancelAllDripCampaign');
    }

    public function suspended($event)
    {
        $this->sendMail($event, "suspended", "suspended");
        $this->destroySessions($event->company);
    }

    public function manually_suspended($event)
    {
        $this->sendMail($event, "suspended", "manually_suspended");
        $this->destroySessions($event->company);
    }

    public function unsubscribed($event)
    {
        $this->sendMail($event, "unsubscribed", "unsubscribed");
        $this->destroySessions($event->company);
    }

    public function reactivated($event)
    {
        $this->sendMail($event, "reactivated", "reactivated");
    }

    public function terminated($event)
    {
        $this->sendMail($event, "terminated", "terminated");
    }

    //CancelDropCompaign
    public function cancelAllDripCampaign($event)
    {
        $company = $event->company;
        App::make('App\Services\DripCampaigns\DripCampaignService')->cancelCompanyCampaign($company->id);
    }

    /******************** Private Section *********************/
    private function sendMail($event, $eventName, $template)
    {
        $company = $event->company;
        $data = $company->subscriber->toArray();
        $data['company_id'] = $company->id;
        $data['company_name'] = $company->name;
        $data['company_email'] = $company->office_email;
        $data['event'] = $eventName;

        Mail::send('emails.subscription.' . $template, $data, function ($message) use ($data, $eventName) {
            $message->subject(trans("response.events.email_subjects." . $eventName));
            if ($data['company_email'] == $data['email']) {
                $message->to($data['email']);
            } else {
                $message->to($data['email'])->cc($data['company_email']);
            }
        });

        // mail to superadmin..
        $superAdmin = User::superAdmin()->toArray();
        Mail::send('emails.subscription.super_admin_notification', $data, function ($message) use ($superAdmin, $eventName) {
            $message->to($superAdmin['email'])->subject("Company " . $eventName);
        });
    }

    // logout all users of company..
    private function destroySessions($company)
    {
        $userIds = $company->allUsers->pluck('id')->toArray();
        DB::table('user_devices')->whereIn('user_id', $userIds)->delete();
        DB::table('oauth_access_tokens')->whereIn('user_id', $userIds)->delete();
    }
}
