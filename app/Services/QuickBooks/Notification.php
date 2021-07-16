<?php
namespace App\Services\QuickBooks;

use MobileNotification;
use App\Repositories\NotificationsRepository;
use Illuminate\Support\Facades\Log;
use Exception;

class Notification
{

    protected $repo;

    function __construct(NotificationsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function send($message, $userId, $sendPushNotificaiton = false)
    {

        $this->sendNotification(null, $userId, $message);

        if($sendPushNotificaiton) {

            $this->sendPushNotification($message, $userId, $message);
        }

        Log::info('QuickBook Notification:' . __METHOD__ , [func_get_args()]);
    }

    private function sendNotification($sender = null, $recipients, $subject, $job = null)
    {
        try {
            $this->repo->notification(
                $sender,
                $recipients,
                $subject,
                $job,
                $body = array(),
                $updateFirebase = false
            );
        } catch (Exception $e) {

            Log::error("QuickBook notificaiton failed", [(string) $e]);
        }
    }

    private function sendPushNotification($title, $users, $description)
    {
        try {

            $type = 'quickbook_notification';

            MobileNotification::send($users, $title, $type, $description);

        } catch (Exception $e) {
            Log::error("QuickBook push notificaiton failed", [(string) $e]);
        }
    }
}