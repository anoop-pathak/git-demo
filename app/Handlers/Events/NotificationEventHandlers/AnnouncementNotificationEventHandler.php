<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Models\User;
use App\Repositories\NotificationsRepository;
use MobileNotification;
use Illuminate\Support\Facades\Auth;

class AnnouncementNotificationEventHandler
{

    protected $repo;

    function __construct(NotificationsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function handle($event)
    {
        $announcement = $event->announcement;
        $meta = $event->meta;

        $notifyTo = ine($meta, 'notify_to') ? $meta['notify_to'] : '';
        switch ($notifyTo) {
            case 'admin_only':
                $users = User::authority();
                break;

            case 'all_users':
                $users = User::whereNotIn('group_id', [User::GROUP_SUPERADMIN]);
                break;

            default:
                $users = User::authority();
                break;
        }

        if (!$announcement->for_all_trades) {
            $trades = $announcement->trades->pluck('id')->toArray();
            $users->whereIn('company_id', function ($query) use ($trades) {
                $query->select('id')->from('companies')->whereIn('id', function ($query) use ($trades) {
                    $query->select('company_id')->from('company_trade')->whereIn('trade_id', $trades);
                });
            });
        }

        $users = $users->pluck('id')->toArray();
        if (!empty($users)) {
            foreach (array_chunk($users, 200) as $key => $users) {
                $this->sendNotification(\Auth::user(), $users, $announcement->title, $announcement);
                if (ine($meta, 'send_push_notification')) {
                    $this->sendPushNotification($announcement->title, $users, $announcement->description);
                }
            }
        }
    }

    private function sendNotification($sender, $recipients, $subject, $job)
    {
        try {
            $this->repo->notification(
                $sender,
                $recipients,
                $subject,
                $job,
                $body = [],
                $updateFirebase = false
            );
        } catch (\Exception $e) {
            //exception..
        }
    }

    private function sendPushNotification($title, $users, $description)
    {
        try {
            $type = 'announcement';
            MobileNotification::send($users, $title, $type, $description);
        } catch (\Exception $e) {
            //exception
        }
    }
}
