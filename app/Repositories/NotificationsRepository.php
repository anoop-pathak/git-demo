<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use Firebase;

class NotificationsRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(Notification $model)
    {
        $this->model = $model;
    }

    // Get unread notification counts..
    public function unreadNotificationCount(User $user)
    {
        $counts = $user->notifications()->count();
        return $counts;
    }

    //get unread notification..
    public function getUnreadNotifications(User $user)
    {
        $notifications = $user->notifications()->orderBy('id', 'desc');
        return $notifications;
    }

    //create notification for a user..
    public function notification(User $sender = null, $recipientsIds, $subject, $job, $body = null, $updateFirebase = true)
    {
        $notification = new Notification;

        $notification->subject($subject)->regarding($job);
        if ($sender) {
            $notification->from($sender);
        }
        $notification->body = $body;
        $notification->deliver();
        $notification->recipients()->attach((array)$recipientsIds);

        //firebase all user count not updated on announcement notification
        if ($updateFirebase) {
            foreach ($notification->recipients as $recipient) {
                Firebase::updateUserNotificationCount($recipient);
            }
        }

        return true;
    }

    //mark the notification as read..
    public function markAsRead(User $user, $notificationId = false)
    {
        $notifications = $user->notifications();

        // if notificationId is false mark all as read..
        if (!$notificationId) {
            $notifications->update(['is_read' => true]);
        } else {
            $notifications->where('notification_id', $notificationId);
            $notifications->update(['is_read' => true]);
        }

        Firebase::updateUserNotificationCount($user);

        return true;
    }
}
