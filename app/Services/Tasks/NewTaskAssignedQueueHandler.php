<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Firebase;
use MobileNotification;
use Illuminate\Support\Facades\Queue;

class NewTaskAssignedQueueHandler
{
	public function fire($queue, $data) {

		if(!ine($data, 'task_id')) {
			return $queue->delete();
		}

		$task = $this->getTask($data['task_id']);
		$assignedBy = $task->createdBy;
		$userIds = $task->participants()->pluck('user_id')->toArray();
		if(empty($userIds)) return $queue->delete();

		setScopeId($task->company_id);
		$title = 'New Task - '.$assignedBy->first_name.' '.$assignedBy->last_name;
		$message = $task->title;
		if (!empty($task->notes)) $message .= ' - '.$task->notes;
		$type = 'new_task';

		if($task->isToday()) {
			foreach ($userIds as $userId) {
				Firebase::updateTodayTask($userId);
			}
		}

		if($task->isUpcoming()) {
			foreach ($userIds as $userId) {
				Firebase::updateUserUpcomingTasks($userId);
			}
		}

		$notificationData = [
			'company_id' => $task->company_id,
		];

		MobileNotification::send($userIds, $title, $type, $message, $notificationData);

		$queueData['id'] = $task->id;

		if(ine($data, 'email_notification')){
			Queue::push('\App\Services\Tasks\TaskQueueHandler@sendEmail', $queueData);
		}

		if(ine($data, 'message_notification')){
			Queue::push('\App\Services\Tasks\TaskQueueHandler@sendMessage', $queueData);
		}

		$queue->delete();
	}

	private function getTask($taskId)
	{
		return Task::find($taskId);
	}
}