<?php

namespace App\Handlers\Events;

use Firebase;
use MobileNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Queue;
use Settings;

class TaskEventHandler
{

    public function subscribe($event)
    {
        //task complete
        $event->listen('JobProgress.Tasks.Events.TaskCompleted', 'App\Handlers\Events\TaskEventHandler@taskCompleted');
        $event->listen('JobProgress.Tasks.Events.TaskCompleted', 'App\Handlers\Events\TaskEventHandler@googleEventMarkAsPendingOrComplete');

        //new task assign
        $event->listen('JobProgress.Tasks.Events.NewTaskAssigned', 'App\Handlers\Events\TaskEventHandler@newTaskAssigned');
        $event->listen('JobProgress.Tasks.Events.NewTaskAssigned', 'App\Handlers\Events\TaskEventHandler@taskCreateOrUpdateOnGoogle');

        //task update
        $event->listen('JobProgress.Tasks.Events.TaskUpdated', 'App\Handlers\Events\TaskEventHandler@taskUpdated');
        $event->listen('JobProgress.Tasks.Events.TaskUpdated', 'App\Handlers\Events\TaskEventHandler@taskCreateOrUpdateOnGoogle');

        //task delete
        $event->listen('JobProgress.Tasks.Events.TaskDeleted', 'App\Handlers\Events\TaskEventHandler@taskDeleted');
    }

    public function taskCompleted($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $task = $event->task;

        if (!$task) {
            return;
        }

        foreach ($task->participants()->pluck('user_id')->toArray() as $userId) {
            if ($task->isToday()) {
                Firebase::updateTodayTask($userId);
            }
            Firebase::updateUserTaskCount($userId);
            Firebase::updateUserUpcomingTasks($userId);
        }

        if ($task->completed_by && $task->notifyUsers) {
            $notifyUsers = $task->notifyUsers;

            $this->sendWebNotification($task, $notifyUsers);
            $this->sendPushNotification($task, $notifyUsers->pluck('id')->toArray());
        }
    }

    public function newTaskAssigned($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
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

        foreach ($userIds as $userId) {
            Firebase::updateUserTaskCount($userId);
        }

        $notificationData = [
			'company_id' => $task->company_id,
		];

        MobileNotification::send($userIds, $title, $type, $message, $notificationData);

        $data['id'] = $task->id;

		if(ine($meta, 'email_notification')){
			Queue::push('\App\Services\Tasks\TaskQueueHandler@sendEmail', $data);
		}

		if(ine($meta, 'message_notification')){
			Queue::connection('sync')->push('\App\Services\Tasks\TaskQueueHandler@sendMessage', $data);
		}
    }

    public function taskUpdated($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $oldTask = $event->task->getOriginal();
        $task = $event->task;

        $users = $task->participants()->pluck('user_id')->toArray();

        $todayDate = Carbon::now(\Settings::get('TIME_ZONE'))->toDateString();

        if (($oldTask['due_date'] == $todayDate) || $task->isToday()) {
            foreach ((array)$users as $userId) {
                Firebase::updateTodayTask($userId);
            }
        }

        foreach ((array)$users as $userId) {
            Firebase::updateUserUpcomingTasks($userId);
        }
    }

    public function taskDeleted($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $task = $event->task;
        $users = array_keys($event->oldUsers);

        foreach ($users as $user) {
            Firebase::updateUserTaskCount($user);

            if ($task->isToday()) {
                Firebase::updateTodayTask($user);
                continue;
            }
        }

        $data['old_users'] = $event->oldUsers;
        Queue::push('App\Services\Tasks\TaskQueueHandler@deleteTaskOnGoogle', $data);
    }

    public function taskCreateOrUpdateOnGoogle($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $data['current_login_id'] = \Auth::id();
        $data['task_id'] = $event->task->id;

        Queue::push('App\Services\Tasks\TaskQueueHandler@createTaskOnGoogle', $data);
    }

    private function sendWebNotification($task, $notifyUsers)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        if (empty($notifyUsers)) {
            return;
        }

        $data = [
            'task_id' => $task->id,
            'current_login_id' => \Auth::id(),
        ];

        Queue::push('App\Services\Tasks\TaskQueueHandler@sendWebNotification', $data);
    }

    private function sendPushNotification($task, $userIds)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
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

    public function googleEventMarkAsPendingOrComplete($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $task = $event->task;

        $data = [
            'task_id' => $task->id,
            'current_login_id' => \Auth::id(),
        ];

        Queue::push('App\Services\Tasks\TaskQueueHandler@googleEventMarkAsPendingOrComplete', $data);
    }
}
