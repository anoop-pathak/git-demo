<?php namespace App\Services\Google;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\AppointmentRecurring;
use App\Models\GoogleClient;
use App\Models\User;
use App\Repositories\AppointmentRepository;
use App\Repositories\GoogleClientRepository;
use App\Services\Appointments\AppointmentService;
use App\Traits\HandleGoogleExpireToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Settings;
use Illuminate\Support\Facades\Log;

class GoogleCalenderSyncService
{

    use HandleGoogleExpireToken;

    protected $client;
    protected $googleClientRepo;
    protected $appointmentRepo;
    protected $appointmentIds = [];

    public function __construct(GoogleClientRepository $googleClientRepo, AppointmentRepository $appointmentRepo, AppointmentService $appointmentService)
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(\config('google.client_id'));
        $this->client->setClientSecret(\config('google.client_secret'));
        $this->client->setState('offline');

        $this->googleClientRepo = $googleClientRepo;
        $this->appointmentRepo = $appointmentRepo;
        $this->appointmentService = $appointmentService;
    }

    public function sync($channelId)
    {
        $gClient = $this->googleClientRepo->findByChannelId($channelId);
        if (!$gClient) {
            return false;
        }

        try {
            $nextSynctoken = $this->getSyncData(
                $gClient->calender_id,
                $gClient->token,
                $gClient->next_sync_token,
                $gClient->user_id
            );
            $gClient->next_sync_token = $nextSynctoken;
            $gClient->save();

        } catch(\Google_Auth_Exception $e){

            if($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($gClient->token);
            }else {
                throw $e;
            }
        } catch(\Google_Service_Exception $e) {

            if($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($gClient->token);
            }else {
                throw $e;
            }
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($gClient->token);
            } elseif(strpos($e->getMessage(), "Unknown or bad timezone") !== false) {
                // print error as warning when unknown or bad timezone exception thrown
                Log::warning("----- Unknown or bad timezone error start -----");
                Log::warning("Unknown or bad timezone error data");
                Log::info("Appointment Id: ".$e->appointment_id);
                Log::info("Appointment Ex Dates: ".$e->exdates);
                Log::warning("----- Unknown or bad timezone error end -----");
            } else {
                throw $e;
            }
        }
    }

    private function getSyncData($calenderId, $token, $syncToken, $userId)
    {
        $user = User::find($userId);
        setScopeId($user->company_id);
        $this->client->setAccessToken($token);
        $calender = new \Google_Service_Calendar($this->client);
        try {
            $optParams = ['syncToken' => $syncToken];
            $events = $calender->events->listEvents($calenderId, $optParams, $user);
        } catch (\Google_Service_Exception $e) {
            //Sync token is no longer valid, a full sync is required. error handle
            if ($e->getCode() == 410) {
                $calenderService = new \App\Services\Google\GoogleCalenderServices;
                $syncToken = $calenderService->calenderFisrtSync($calenderId, $token);
                $optParams = ['syncToken' => $syncToken];
                $events = $calender->events->listEvents($calenderId, $optParams, $user);
            } else {
                throw $e;
            }
        }

        while (true) {
            foreach ($events->getItems() as $event) {
                $result = [];

                if (substr($event->id, 0, 8) == 'schedule') {
                    continue;
                }
                if (substr($event->recurringEventId, 0, 8) == 'schedule') {
                    continue;
                }

                //break for if attendess doing any changes
                if ($event->organizer) {
                    $organizerEmail = $event->organizer->email;
                    $organizerClient = GoogleClient::where('email', $organizerEmail)
                        ->whereNull('company_id')
                        ->calendar()
                        ->first();

                    $continue = false;
                    foreach ($event->getAttendees() as $attendee) {
                        if ($attendee->getSelf() && ($organizerClient) && ($attendee->email != $organizerEmail)) {
                            $continue = true;
                        }
                    }
                    if ($continue) {
                        continue;
                    }
                }


                if ($event->status == "cancelled") {
                    $canceldAppointment = Appointment::where('google_event_id', $event->id)->first();
                    if ($canceldAppointment
                        && $canceldAppointment->user_id
                        && $canceldAppointment->user_id != $user->id) {
                        continue;
                    }
                }

                //first appointment recurring or without recurring
                if (!$event->recurringEventId && ($event->getStatus() == "confirmed")) {
                    if (count($ids = explode('_', $event->id)) > 1) {
                        $a = Appointment::where('google_event_id', $ids[0])->withTrashed()->first();
                        $event->parent_id = ($a) ? $a->id : null;
                    }

                    $appointment = $this->appointmentRepo->findByEventId($event->id);

                    if ((!$appointment && (substr($event->id, 0, 11) == 'appointment'))
                        && (!strpos($event->id, "_"))) {
                        continue;
                    }

                    $this->createAppointment($event, $user, $appointment);
                    continue;
                }

                $dateTimefilters = [];

                $originalDateTime = $event->getOriginalStartTime();
                if ($originalDateTime) {
                    $dateTimefilters['start_date_time'] = $originalDateTime->dateTime;
                    $dateTimefilters['timezone'] = $originalDateTime->timeZone;
                    $dateTimefilters['start_date'] = $originalDateTime->date;
                }

                //single recurring appointment update on google
                if ($event->recurringEventId && ($event->getStatus() == "confirmed")) {
                    $appointment = $this->appointmentRepo->findByEventId($event->recurringEventId, $dateTimefilters);
                    $descAppointment = $appointment;

                    if ((!$appointment && (substr($event->id, 0, 11) == 'appointment'))
                        && (!strpos($event->id, "_"))) {
                        continue;
                    }

                    if ($appointment) {
                        $event->series_id = $appointment->series_id;
                        $appointment->deleteRecurring();
                        $descAppointment = $appointment;
                        $appointment = null;
                    } else {
                        $withoutRepeat = ['without_recurring' => true];
                        $appointment = $this->appointmentRepo->findByEventId($event->id, $withoutRepeat);
                    }

                    $this->createAppointment($event, $user, $appointment, $descAppointment);
                    continue;
                }

                if ($event->recurringEventId && ($event->getStatus() == 'cancelled')) {
                    $appointment = $this->appointmentRepo->findByEventId($event->getId(), ['without_recurring' => true]);
                    if ($appointment) {
                        $appointment->delete();
                        continue;
                    }

                    $appointment = $this->appointmentRepo->findByEventId($event->getId(), $dateTimefilters);
                    // delete after renaming on google
                    if ($appointment) {
                        $appointment->deleteRecurring();
                        continue;
                    }

                    $appointment = $this->appointmentRepo->findByEventId($event->recurringEventId, $dateTimefilters);

                    if ($appointment) {
                        $appointment->deleteRecurring();
                        continue;
                    }

                    $appointment = $this->appointmentRepo->findByEventId($event->getId());
                    if (!$appointment) {
                        continue;
                    }

                    AppointmentRecurring::where('appointment_id', $appointment->id)->delete();
                    Appointment::where('id', $appointment->id)->delete();
                }

                //when recurring appointment delete
                if (!($event->recurringEventId) && ($event->getStatus() == 'cancelled')) {
                    $appointment = Appointment::where('google_event_id', $event->getId())->first();
                    if ($appointment) {
                        AppointmentRecurring::where('appointment_id', $appointment->id)
                            ->delete();
                        Appointment::where('id', $appointment->id)
                            ->delete();
                    }
                    continue;
                }
            }

            $pageToken = $events->getNextPageToken();
            $syncToken = $events->getNextSyncToken();

            if ($pageToken) {
                $optParams = [
                    'pageToken' => $pageToken,
                    'syncToken' => $syncToken
                ];
                $events = $calender->events->listEvents($calenderId, $optParams, $user);
            } else {
                break;
            }
        }

        return $events->getNextSyncToken();
    }

    private function createAppointment($event, $user, $appointment = null, $descAppointment = null)
    {
        $attendeeName = $invites = $attendees = [];
        $userFor = null;
        $organizerDisplayName = null;
        $organizerEmail = null;

        $companyId = $user->company_id;
        if ($organizer = $event->organizer) {
            $organizerEmail = $organizer->email;
            $organizerDisplayName = $organizer->displayName . " ({$organizerEmail})";
        }

        $googleClient = $user->googleCalendarClient;

        if ($organizerEmail) {
            $organizerClient = GoogleClient::where('email', $organizerEmail)
                ->whereNull('company_id')
                ->calendar()
                ->first();
            $userFor = ($organizerClient) ? $organizerClient->user_id : null;
        }

        foreach ($event->getAttendees() as $ga) {
            $email = $ga->getEmail();

            // if creator exist in jp. does not create for attendee..
            if ($userFor
                && ($ga->email)
                && ($ga->getSelf())
                && ($ga->email != $organizerEmail)) {
                return false;
            }

            $attendeeName[] = $ga->displayName . " ({$email})";

            $aClient = GoogleClient::whereNull('company_id')
                ->calendar()
                ->whereEmail($email)
                ->whereHas('user', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })->first();

            if ($aClient) {
                $attendees[] = $aClient->user_id;
                continue;
            }

            $atteUser = User::where('company_id', $companyId)
                ->where('email', $ga->email)
                ->select('id')
                ->first();

            if ($atteUser) {
                $attendees[] = $atteUser->id;
                continue;
            }

            $invites[] = $ga->email;
        }

        $eventData['title'] = (trim($event->getSummary()) != '') ? $event->getSummary() : "";
        $eventData['description'] = (trim($event->getDescription()) != '') ? $event->getDescription() : "";
        $eventData['location'] = $event->getLocation();
        $start = $event->getStart();

        $settings = Settings::forUser($user->id, $user->company_id);
        $tz = $settings->get('TIME_ZONE');
        Log::info("Setting User Id: {$user->id} Company Id:{$user->company_id} Timezone: {$tz}");

        if (!is_null($start->dateTime)) {
            $startDateTime = $event->getStart()->dateTime;
            $endDateTime = $event->getEnd()->dateTime;
            if ($event->getStart()->timeZone) {
                Log::info("Google Calendar Start Date Timezone: {$event->getStart()->timeZone}");
                Log::info("Google Calendar End Date Timezone: {$event->getEnd()->timeZone}");
                $startDateTime = Carbon::parse($startDateTime, $event->getStart()->timeZone);
                $startDateTime->setTimezone('UTC');
                $startDateTime = Carbon::parse($startDateTime->toDateTimeString(), 'UTC');
                $startDateTime->setTimezone($tz);
                $startDateTime = $startDateTime->toDateTimeString();

                $endDateTime = Carbon::parse($endDateTime, $event->getEnd()->timeZone);
                $endDateTime->setTimezone('UTC');
                $endDateTime = Carbon::parse($endDateTime->toDateTimeString(), 'UTC');
                $endDateTime->setTimezone($tz);
                $endDateTime = $endDateTime->toDateTimeString();
            } else {
                $startDateTime = Carbon::parse($startDateTime);
                $startDateTime->setTimezone($tz);
                $startDateTime = $startDateTime->toDateTimeString();

                $endDateTime = Carbon::parse($endDateTime);
                $endDateTime->setTimezone($tz);
                $endDateTime = $endDateTime->toDateTimeString();
            }
            $eventData['full_day'] = false;
        } else {
            $eventData['full_day'] = true;
            $date = new Carbon($start->date, $tz);
            $startDateTime = $date->toDateTimeString();
            $endDateTime = $date->addHours(23)->addMinutes(59)->toDateTimeString();
        }

        $eventData['user_id'] = null;
        $eventData['jp_to_google_sync'] = false;

        if ($userFor) {
            $eventData['user_id'] = $userFor;
            $eventData['jp_to_google_sync'] = true;
        }

        $jobIds = [];
        if ($descAppointment) {
            $eventData['location_type'] = $descAppointment->location_type;
            $jobIds = $descAppointment->jobs()->select('jobs.id')->pluck('id')->toArray();
            $eventData['customer_id'] = $descAppointment->customer_id;
            $eventData['parent_id'] = $descAppointment->id;
        }

        $eventData['google_event_id'] = $event->getId();
        $eventData['company_id'] = $user->company_id;
        $eventData['exdates'] = null;
        if ($recurrence = $event->getRecurrence()) {
            $deletedRecurringCount = 0;
            if (count($recurrence) > 1) {
                $eventData['exdates'] = $recurrence[0];
                if ($appointment) {
                    DB::table('appointment_recurrings')
                        ->where('appointment_id', $appointment->id)
                        ->whereNotNull('deleted_at')
                        ->update(['deleted_at' => null, 'deleted_by' => null]);
                }
                unset($recurrence[0]);
            }

            $recurRule = str_replace('RRULE:', '', reset($recurrence));
            $ruleArray = explode(';', $recurRule);
            for ($i = 0; $i < count($ruleArray); $i++) {
                $ruleAndValue = explode('=', $ruleArray [$i]);
                $rules[$ruleAndValue [0]] = $ruleAndValue[1];
            }
            $eventData['repeat'] = strtolower($rules['FREQ']);
            $eventData['occurence'] = issetRetrun($rules, 'COUNT') ?: 0;
            $eventData['interval'] = issetRetrun($rules, 'INTERVAL') ?: 1;
            $eventData['by_day'] = null;
            if (ine($rules, 'BYDAY')) {
                $eventData['by_day'] = explode(',', $rules['BYDAY']);
            }

            if (ine($rules, 'UNTIL')) {
                $eventData['until_date'] = \Carbon\Carbon::parse($rules['UNTIL'], $tz)->toDateTimeString();
            }
        }

        if ($appointment) {
            $this->appointmentService->updateGoogleAppointment($appointment, $eventData, $startDateTime, $endDateTime, $attendees, $invites);
        } else {
            $desc = "";
            if ($eventData['description']) {
                $desc .= PHP_EOL . PHP_EOL;
            }

            $desc .= 'Organizer: ' . $organizerDisplayName;
            if (!empty($attendeeName)) {
                $desc .= PHP_EOL . 'Attendees: ' . implode(', ', $attendeeName);
            }

            $eventData['description'] = $eventData['description'] . $desc;
            $eventData['series_id'] = ($event->series_id) ?: null;
            $appointment = $this->appointmentService->createGoogleAppointment($eventData, $startDateTime, $endDateTime, $attendees, $jobIds, $invites);
        }

        // $this->saveReminders($appointment, $event);

        return $appointment;
    }

    /**
     * save appointment reminders
     * @param  $appointment
     * @param  $event
     * @return $appointment
     */
    private function saveReminders($appointment, $event)
    {
        $reminders = $event->getReminders()->getOverrides();
        $appointment->reminders()->delete();
        if(empty($reminders)) return $appointment;
        $data = [];
        foreach ($reminders as $key => $reminder) {
            if(!ine($reminder, 'method') || !ine($reminder, 'minutes')) continue;
            $data[] = new AppointmentReminder ([
                'appointment_id' => $appointment->id,
                'type'           => $reminder['method'],
                'minutes'        => $reminder['minutes'],
            ]);
        }
        if(!empty($data)) {
            $appointment->reminders()->saveMany($data);
        }

        return $appointment;
    }
}
