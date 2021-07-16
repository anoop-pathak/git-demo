<?php 

namespace App\Handlers\Events;

use Queue;

class MessageEventHandler {
 	public function subscribe($event) {
		$event->listen('JobProgress.Messages.Events.MarkAsReadEvent', 'App\Handlers\Events\MessageEventHandler@markAsRead');
	}
 	public function markAsRead($event)
	{
		Queue::push('App\Handlers\Events\MessagesQueueHandler@markAsRead', ['user_id' => $event->userId]);
	}
} 