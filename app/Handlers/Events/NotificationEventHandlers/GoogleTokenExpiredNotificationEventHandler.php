<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use Illuminate\Support\Facades\Mail;

class GoogleTokenExpiredNotificationEventHandler
{

    protected $repo;

    function __construct(NotificationsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function handle($event)
    {
        $googleClient = $event->googleClient;

        $user = null;

        if ($googleClient->isCompanyAccount()) {
            $company = $googleClient->company;
            $user = $company->subscriber;
            $msg = trans('response.events.notifications.company_google_account_reconnect');
            $emailTemplate = 'emails.company_google_account_reconnect';
        } else {
            $user = $googleClient->user;
            $msg = trans('response.events.notifications.user_google_account_reconnect');
            $emailTemplate = 'emails.user_google_account_reconnect';
        }

        if ($user) {
            $this->repo->notification(null, $user->id, $msg, $user);

            $data = [
                'first_name' => $user->first_name,
                'email' => $user->email,
                'google_account' => $googleClient->email,
            ];

            Mail::send($emailTemplate, $data, function ($message) use ($data) {
                $message->to($data['email'])->subject(trans('response.events.email_subjects.google_reconnect'));
            });

            $googleClient->delete();
        }
    }
}
