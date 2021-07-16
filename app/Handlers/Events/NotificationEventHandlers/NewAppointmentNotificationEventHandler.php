<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Models\UserDevice;
use MobileNotification;
use Settings;

class NewAppointmentNotificationEventHandler
{

    public function handle($event)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $appointment = $event->appointment;
        $attendees = $appointment->attendees->pluck('id')->toArray();

        //check appointment user
        if (($user = $appointment->user)) {
            $attendees[] = $user->id;
        }

        if (isset($event->previousData) && !empty($event->previousData)) {
            $previousData = $event->previousData;
            $previousAttendees = $previousData['previous_attendees'];
            $previousAttendees[] = $previousData['previous_user_id'];
            $attendees = array_diff($attendees, $previousAttendees);
        }

        $device = UserDevice::whereIn('user_id', $attendees)
            ->whereNotNull('device_token')
            ->count();

        if (!$device) {
            return;
        }

        $title = 'New Appointment';

        if ($appointment->customer) {
            $info = 'You have a new appointment with ' . $appointment->customer->full_name_mobile;
        } else {
            $info = $appointment->title;
        }

        $type = 'new_appointment';
        $data = [
			'company_id' => $appointment->company_id,
		];
        foreach (array_unique($attendees) as $userId) {
            $timezone = Settings::forUser($userId)->get('TIME_ZONE');
            $dateTime = convertTimezone($appointment->start_date_time, $timezone);
            $message = $info . ' at ' . $dateTime->format('h:ia') . ' on ' . $dateTime->format('m-d-Y');

            MobileNotification::send($userId, $title, $type, $message, $data);
        }
    }
}
