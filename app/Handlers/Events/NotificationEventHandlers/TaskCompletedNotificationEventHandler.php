<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use MobileNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class TaskCompletedNotificationEventHandler
{

    public function handle($event)
    {
        $task = $event->task;
        $notifyUsers = $task->notifyUsers;

        $this->sendWebNotification($task, $notifyUsers);
        $this->sendPushNotification($task, $notifyUsers->pluck('id')->toArray());
    }

    private function sendWebNotification($task, $notifyUsers)
    {
        $subject = 'Task Completed';
        $sender = \Auth::user();
        $body = json_encode([
            'completed_by' => $sender->full_name,
        ]);
        $notificationRepo = App::make(\App\Repositories\NotificationsRepository::class);

        foreach ($notifyUsers as $key => $recepient) {
            $notificationRepo->notification($sender, $recepient->id, $subject, $task, $body, $updateFirebase = true);
        }
    }

    private function sendPushNotification($task, $userIds)
    {
        if (empty($userIds)) {
            return false;
        }

        $type = 'task_completed';
        $title = 'Task Completed';
        $message = $task->title;

        $data = [
			'company_id' => $task->company_id,
		];

        MobileNotification::send($userIds, $title, $type, $message, $data);
    }
}
