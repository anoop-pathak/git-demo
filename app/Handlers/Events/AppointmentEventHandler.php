<?php

namespace App\Handlers\Events;

use App\Repositories\AppointmentRepository;
use App\Repositories\JobNotesRepository;
use Firebase;
use Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class AppointmentEventHandler
{

    public function __construct(JobNotesRepository $jobNoteRepo, AppointmentRepository $repo)
    {
        $this->repo = $repo;
        $this->jobNoteRepo = $jobNoteRepo;
    }

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('JobProgress.Appointments.Events.AppointmentCreated', 'App\Handlers\Events\AppointmentEventHandler@firebaseUpdate');
        $event->listen('JobProgress.Appointments.Events.AppointmentCreated', 'App\Handlers\Events\AppointmentEventHandler@insertGoogleCalenderEvent');
        $event->listen('JobProgress.Appointments.Events.AppointmentCreated', 'App\Handlers\Events\AppointmentEventHandler@insertJobNote');
        $event->listen('JobProgress.Appointments.Events.AppointmentUpdated', 'App\Handlers\Events\AppointmentEventHandler@updateGoogleCalenderEvent');
        $event->listen('JobProgress.Appointments.Events.AppointmentUpdated', 'App\Handlers\Events\AppointmentEventHandler@updateJobNote');
        $event->listen('JobProgress.Appointments.Events.AppointmentUpdated', 'App\Handlers\Events\AppointmentEventHandler@firebaseUpdate');
        $event->listen('JobProgress.Appointments.Events.AppointmentDeleted', 'App\Handlers\Events\AppointmentEventHandler@deleteJobNote');
        $event->listen('JobProgress.Appointments.Events.AppointmentDeleted', 'App\Handlers\Events\AppointmentEventHandler@firebaseUpdate');
        $event->listen('JobProgress.Appointments.Events.OldRecurringAppointmentUpdated', 'App\Handlers\Events\AppointmentEventHandler@updateGoogleCalenderEvent');
        $event->listen('JobProgress.Appointments.Events.AppointmentJobNoteUpdated', 'App\Handlers\Events\AppointmentEventHandler@updateJobNote');
        $event->listen('JobProgress.Appointments.Events.DeleteGoogleAppointment', 'App\Handlers\Events\AppointmentEventHandler@deleteGoogleAppointment');

        $event->listen('JobProgress.Appointments.Events.CreateGoogleAppointment', 'App\Handlers\Events\AppointmentEventHandler@insertGoogleCalenderEvent');
    }

    public function insertGoogleCalenderEvent($event)
    {
        if(!config('notifications.enabled')) {
			return true;
        }

        $appointment = $event->appointment;
        if ($appointment->user_id) {
            $data = [
                'previous_user_id' => null,
                'user_id' => $appointment->user_id,
                'appointment_id' => $appointment->id,
                'recurring_id' => $appointment->recurring_id
            ];
            Queue::push('\App\Services\Queue\GoogleAppointmentQueueHandler@insert', $data);
        }
    }

    public function insertJobNote($event)
    {
        if(!config('notifications.enabled')) {
			return true;
        }
        $appointment = $event->appointment;
        $this->addNote($appointment, 'created');
    }

    /**
     * add job note on update appointment
     * @param  [type] $event [description]
     * @return [type]        [description]
     */
    public function updateJobNote($event)
    {
        if(!config('notifications.enabled')) {
			return true;
        }
        $appointment = $event->appointment;
        $this->addNote($appointment, 'updated');
    }

    /**
     * Add job note on delete of appointment
     * @param  [type] $event [description]
     * @return [type]        [description]
     */
    public function deleteJobNote($event)
    {
        if(!config('notifications.enabled')) {
			return true;
        }
        $appointment = $event->appointment;
        $this->addNote($appointment, 'deleted');
    }

    public function updateGoogleCalenderEvent($event)
    {
        if(!config('notifications.enabled')) {
            return true;
        }
        try {
            $appointment = $event->appointment;
            $previousData = $event->previousData;

            $previousUserId = null;
            if (ine($previousData, 'previous_user_id')
                && ($previousData['previous_user_id'] != $appointment->user_id)) {
                $previousUserId = $previousData['previous_user_id'];
            }

            $data = [
                'appointment_id' => $appointment->id,
                'recurring_id' => $appointment->recurring_id,
                'user_id' => $appointment->user_id,
                'previous_user_id' => $previousUserId,
                'google_event_id' => $appointment->google_event_id
            ];

            Queue::push('\App\Services\Queue\GoogleAppointmentQueueHandler@update', $data);
        } catch (\Exception $e) {
            throw $e;
            // Log::error('Google Event delete error :'. getErrorDetail($e));
        }
    }

    public function deleteGoogleAppointment($event)
    {
        if(!config('notifications.enabled')) {
			return true;
        }
        try {
            $appointment = $event->appointment;
            $previousData = $event->previousData;

            $data = [
                'appointment_id' => $appointment->id,
                'recurring_id' => $appointment->recurring_id,
                'user_id' => $appointment->user_id,
                'google_event_id' => $appointment->google_event_id
            ];

            Queue::push('\App\Services\Queue\GoogleAppointmentQueueHandler@delete', $data);
        } catch (\Exception $e) {
            Log::error('Google Event delete error :' . getErrorDetail($e));
        }
    }

    public function firebaseUpdate($event)
    {
        if(!config('notifications.enabled')) {
            return true;
		}
        $appointment = $event->appointment;
        $userIds = $appointment->attendees->pluck('id')->toArray();

        //check appointment user exist
        if (($appointment->user)) {
            $userIds[] = $appointment->user_id;
        }

        foreach (arry_fu($userIds) as $userId) {
            Firebase::updateUserUpcomingAppointments($userId);
            Firebase::updateTodayAppointment($userId);
        }
    }

    /**
     * Add Job Note
     * @param [type] $appointment [object]
     * @param [type] $mode        [created, deleted, updated]
     */
    private function addNote($appointment, $mode)
    {
        if(!config('notifications.enabled')) {
            return true;
        }

        if (!$jobs = $appointment->jobs) {
            return false;
        }

        $userName = null;
        $timezone = Settings::get('TIME_ZONE');

        if ($appointment->full_day) {
            $startDateTimeObject = new \Carbon\Carbon($appointment->start_date_time);
            $startDateTime = $startDateTimeObject->format('jS M, Y');
        } else {
            $startDateTimeObject = convertTimezone($appointment->start_date_time, $timezone);
            $startDateTime = $startDateTimeObject->format('jS M, Y \\a\\t h:i a');
        }
        $startDateTime .= '(' . $timezone . ')';

        $attendees = $appointment->attendees->pluck('full_name')->toArray();

        if (($user = $appointment->user)) {
            $userName = $user->full_name;
        }

        foreach ($jobs as $job) {
            $jobWorkflow = $job->jobWorkflow;
            $stage_code = $jobWorkflow->stage->code;
            $note = "An appointment has been $mode with " . $job->customer->full_name;
            $note .= " / " . $job->number;

            if ($userName) {
                $note .= " for $userName";
            }

            $note .= " on $startDateTime.";
            if (!empty($attendees)) {
                $lastAttendees = array_slice($attendees, -1);
                $firstAttendees = join(', ', array_slice($attendees, 0, -1));
                $both = array_filter(array_merge([$firstAttendees], $lastAttendees), 'strlen');
                $note .= ' Attendees - ' . join(' & ', $both);
            }
            $this->jobNoteRepo->saveNote(
                $job->id,
                $note,
                $jobWorkflow->stage->code,
                \Auth::id(),
                $appointment->id
            );
        }
    }
}
