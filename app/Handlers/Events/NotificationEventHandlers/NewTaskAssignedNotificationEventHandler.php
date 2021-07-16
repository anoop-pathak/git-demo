<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use Firebase;
use MobileNotification;
use Queue;

class NewTaskAssignedNotificationEventHandler
{

    public function handle($event)
    {
        $task = $event->task;
        $meta = $event->meta;
        $assignedBy = $task->createdBy;
        $userIds = $task->participants()->pluck('user_id')->toArray();
        if (empty($userIds)) {
            return false;
        }
        $title = 'New Task - ' . $assignedBy->first_name . ' ' . $assignedBy->last_name;
        $message = $task->title;
        if (!empty($task->notes)) {
            $message .= ' - ' . $task->notes;
        }
        $type = 'new_task';

        if ($task->isToday()) {
            foreach ($userIds as $userId) {
                Firebase::updateTodayTask($userId);
            }
        }

        if ($task->isUpcoming()) {
            foreach ($userIds as $userId) {
                Firebase::updateUserUpcomingTasks($userId);
            }
        }
        $notificationData = [
			'company_id' => $task->company_id,
		];

        MobileNotification::send($userIds, $title, $type, $message, $notificationData);

        $data = $task;

        if (isset($meta['email_notification']) && !empty($meta['email_notification'])) {
            Queue::push('App\Tasks\TaskQueueHandler@sendEmail', $data);
        }

        if (isset($meta['message_notification']) && !empty($meta['message_notification'])) {
            Queue::push('App\Tasks\TaskQueueHandler@sendMessage', $data);
        }
    }
}
