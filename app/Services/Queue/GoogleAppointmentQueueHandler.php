<?php

namespace App\Services\Queue;

use App\Models\Appointment;
use App\Models\User;
use App\Repositories\AppointmentRepository;
use App\Services\Google\GoogleCalenderServices;
use Illuminate\Support\Facades\Log;

class GoogleAppointmentQueueHandler
{

    public function __construct(AppointmentRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Notification
     * @param  object $job queue object
     * @param  array $data array data
     * @return Void
     */
    public function insert($queueJob, $data)
    {
        try {
            if (!ine($data, 'user_id')
                || !ine($data, 'recurring_id')) {
                return $queueJob->delete();
            }

            $appointment = $this->repo->getUserRecurringAppointment($data['recurring_id'], $data['user_id']);
            if (!$appointment) {
                return $queueJob->delete();
            }

            $user = $appointment->user;
            if (!$user) {
                return $queueJob->delete();
            }

            $calendarClient = $user->googleCalendarClient;
            if (!$calendarClient) {
                return $queueJob->delete();
            }

            if ($appointment->user_id != $data['user_id']) {
                return $queueJob->delete();
            }

            setScopeId($appointment->company_id);

            $this->createGoogleEvent($appointment);

            $queueJob->delete();
        } catch (\Google_Service_Exception $e) {
            Log::error($e);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Delete google event
     * @param  object $queueJob Queue Job
     * @param  array $data data
     * @return void
     */
    public function delete($queueJob, $data)
    {
        try {
            if (!ine($data, 'user_id')
                || !ine($data, 'google_event_id')) {
                return $queueJob->delete();
            }

            $user = User::find($data['user_id']);
            $this->removeFromGoogleCalender($user, $data['google_event_id']);

            $queueJob->delete();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Update Event
     * @param  object $queueJob queueJob
     * @param  Arary $data data
     * @return void
     */
    public function update($queueJob, $data)
    {
        try {
            if (!ine($data, 'recurring_id') || !isset($data['user_id'])) {
                return $queueJob->delete();
            }

            $userId = $data['user_id'] ?: null;
            $previousUserId = $data['previous_user_id'] ?: null;

            $with = ['user.googleCalendarClient'];
            $appointment = $this->repo->getUserRecurringAppointment($data['recurring_id'], null, $with);
            if (!$appointment) {
                return $queueJob->delete();
            }

            //if queue run after long time what appointment user id changed many times
            $appointmentedUpdatedQueue = ($appointment->user_id != $userId);
            if ($appointmentedUpdatedQueue) {
                if ($appointment->google_event_id && $previousUserId) {
                    $eventId = $appointment->google_event_id;
                    $user = User::find($previousUserId);
                    $this->removeFromGoogleCalender($user, $eventId);
                }

                return $queueJob->delete();
            }

            setScopeId($appointment->company_id);

            $previousUserId = null;
            if (ine($data, 'previous_user_id')) {
                $user = User::find($data['previous_user_id']);
                $previousUserId = $data['previous_user_id'];
            } else {
                $user = $appointment->user;
            }

            $calendarClient = ($user) ? $user->googleCalendarClient : null;
            if (!$calendarClient) {
                return $queueJob->delete();
            }

            $oldGoogleEventId = $appointment->google_event_id;

            $calendarId = null;
            $accessToken = null;
            $appointmentId = $appointment->id;
            if ($calendarClient) {
                $calendarId = $calendarClient->calender_id;
                $accessToken = $calendarClient->token;
            }

            if ($appointment->jp_to_google_sync && ($appointment->user_id)) {
                //appointment maintain yes/no case
                $this->manageOldRecurringEvents(
                    $user,
                    $appointment->id,
                    $calendarId,
                    $accessToken,
                    $oldGoogleEventId
                );
                $this->createGoogleEvent($appointment);
            }

            if (($appointment->jp_to_google_sync)
                && ($user)
                && ($oldGoogleEventId)
                && ($previousUserId)
                && ($previousUserId != $appointment->user_id)
                && ($accessToken)
                && ($calendarId)) {
                //appointment maintain yes/no case
                $this->manageOldRecurringEvents(
                    $user,
                    $appointment->id,
                    $calendarId,
                    $accessToken,
                    $oldGoogleEventId
                );
                $googleCalender = new GoogleCalenderServices;
                $googleCalender->eventDelete($calendarId, $accessToken, $oldGoogleEventId, $calendarClient->user_id);
            }
            $queueJob->delete();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    /******************* PRIVATE METHOOD *************************/
    private function manageOldRecurringEvents($user, $appointmentId, $calendarId, $accessToken, $googleEventId)
    {
        if (!($calendarId && $accessToken && $googleEventId)) {
            return false;
        }

        //update google recurring if attendees yes/no mark
        $appointments = Appointment::whereRaw("google_event_id LIKE '%{$googleEventId}%'")
            ->where('user_id', $user->id)
            ->where('company_id', $user->company_id)
            ->where('id', '!=', $appointmentId)
            ->get();

        foreach ($appointments as $appointment) {
            $eId = $appointment->google_event_id;
            $appointment->google_event_id = null;
            $appointment->save();
            $googleCalender = new GoogleCalenderServices;
            $googleCalender->eventDelete($calendarId, $accessToken, $eId, $appointment->user_id);
            $this->updateGoogleEvent($appointment);
        }
    }

    private function updateGoogleEvent($appointment)
    {
        $appointment = $this->repo->getFirstRecurringAppointment($appointment->id, $withTrashed = true);
        if (!$appointment) {
            return false;
        }

        if (!$appointment->jp_to_google_sync) {
            return false;
        }

        $user = $appointment->user;
        if (!$user) {
            return false;
        }

        $calendarClient = ($user) ? $user->googleCalendarClient : null;
        if (!$calendarClient) {
            return false;
        }

        if (($appointment->google_event_id)) {
            $calendarId = $calendarClient->calender_id;
            $accessToken = $calendarClient->token;
            $attendees = $appointment->attendees->pluck('email')->toArray();
            $attendeesList = array_filter(array_merge((array)$appointment->invites, $attendees));
            $googleCalender = new GoogleCalenderServices;
            $googleCalender->update(
                $calendarId,
                $appointment->google_event_id,
                $accessToken,
                $appointment,
                $appointment->start_date_time,
                $appointment->end_date_time,
                $attendeesList,
                $calendarClient->user_id,
                $appointment->google_event_id
            );
        } else {
            $this->createGoogleEvent($appointment);
        }

        return true;
    }

    private function createGoogleEvent($appointment)
    {
        $user = $appointment->user;
        if (!$user) {
            return null;
        }

        $calendarClient = $user->googleCalendarClient;
        if (!$calendarClient) {
            return null;
        }

        $calendarId = $calendarClient->calender_id;
        $accessToken = $calendarClient->token;
        $attendees = $appointment->attendees->pluck('email')->toArray();
        $attendeesList = array_filter(array_merge((array)$appointment->invites, $attendees));

        $googleCalender = new GoogleCalenderServices;

        $eventExist = false;
        if ($appointment->google_event_id) {
            $eventExist = (boolean)$googleCalender->getEventById($calendarId, $appointment->google_event_id, $accessToken);
        }

        if (!($eventExist)) {
            $appointment->google_event_id = 'appointment' . generateUniqueToken();
            $appointment->save();
            $googleCalender->insert(
                $calendarId,
                $accessToken,
                $appointment,
                $appointment->start_date_time,
                $appointment->end_date_time,
                $attendeesList,
                $calendarClient->user_id,
                $appointment->google_event_id
            );
        } else {
            $googleCalender->update(
                $calendarId,
                $appointment->google_event_id,
                $accessToken,
                $appointment,
                $appointment->start_date_time,
                $appointment->end_date_time,
                $attendeesList,
                $calendarClient->user_id,
                $appointment->google_event_id
            );
        }
    }

    private function removeFromGoogleCalender($user, $googleEventId)
    {
        if (!$user) {
            return false;
        }

        $calendarClient = $user->googleCalendarClient;

        if (!$calendarClient) {
            return false;
        }

        $appointment = Appointment::where('google_event_id', $googleEventId)
            ->where('user_id', $user->id)
            ->withTrashed()
            ->first();
        if ($appointment) {
            $appointment->google_event_id = null;
            $appointment->save();
        }

        $calendarId = $calendarClient->calender_id;
        $accessToken = $calendarClient->token;

        //for mangage yes/no appointment delete
        $this->manageOldRecurringEvents($user, $appointment->id, $calendarId, $accessToken, $googleEventId);

        $googleCalender = new GoogleCalenderServices;
        $googleCalender->eventDelete(
            $calendarClient->calender_id,
            $calendarClient->token,
            $googleEventId
        );
    }
}
