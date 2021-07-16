<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use Firebase;
use MobileNotification;
use Queue;

class NewMessageNotificationEventHandler
{

    public function handle($event)
    {
        $message = $event->message;
        $participantIds = $event->participantIds;
        $sender = $message->sender;

        Queue::push('\App\Handlers\Events\MessagesQueueHandler@updateFirebaseMessageCount', [
            'current_user_id' => \Auth::id(),
            'participant_ids' => $participantIds
        ]);

        $messageContents = '';
        if ($message->subject) {
            $messageContents .= $message->subject . ' - ';
        }

        $title = 'New Message';
		if ($sender) {
			$title = 'New Message - '.$sender->first_name.' '.$sender->last_name;
		}
        $messageContents .= $message->content;
        $type = 'new_message';

        $meta = [
            'thread_id' => $message->thread_id,
            'company_id' => $message->company_id,
            'type' => $message->thread->type
        ];

        MobileNotification::send($participantIds, $title, $type, $messageContents, $meta);
    }
}
