<?php

namespace App\Services\Google;

use Settings;
use Carbon\Carbon;
use Google_Service_Calendar;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Services\Recurr\RecurrService;
use App\Traits\HandleGoogleExpireToken;

class GoogleCalenderServices
{

    use HandleGoogleExpireToken;

    protected $client;

    public function __construct()
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(\config('google.client_id'));
        $this->client->setClientSecret(\config('google.client_secret'));
        $this->client->setState('offline');
    }

    public function createNewCalender($accessToken)
    {
        $this->client->setAccessToken($accessToken);
        $calendar = new Google_Service_Calendar($this->client);
        $newCalendar = new \Google_Service_Calendar_Calendar();
        $newCalendar->setSummary(\config('google.default_calender'));
        $createdCalendar = $calendar->calendars->insert($newCalendar);

        return $createdCalendar->getId();
    }

    public function calenderFisrtSync($calenderId, $accessToken)
    {
        $this->client->setAccessToken($accessToken);
        $calender = new Google_Service_Calendar($this->client);

        $optParams = [];
        do {
            $events = $calender->events->listEvents($calenderId, $optParams);
            $optParams['pageToken'] = $events->nextPageToken;
        } while ($events->nextPageToken != null);

        return $events->getNextSyncToken();
    }

    public function calenderWatch($userId, $calenderId, $accessToken)
    {
        try {
            $this->client->setAccessToken($accessToken);
            $calender = new Google_Service_Calendar($this->client);
            $channel = new \Google_Service_Calendar_Channel($calender);
            $channel->setId($userId . '-events-' . mt_rand());
            $channel->setType('web_hook');
            $channel->setAddress(\config('google.channel_address'));

            $timetoExpire = time() + 3600000;
            $optParams = ['ttl' => $timetoExpire];
            $channel->setParams($optParams);
            return $calender->events->watch($calenderId, $channel);
        } catch (\Exception $e) {
            Log::error('Calendar watch error :' . $e->getMessage());
            return false;
        }
    }

    public function stopCalendarWatch($channelId, $resourceId, $accessToken)
    {
        try {
            $this->client->setAccessToken($accessToken);
            $calender = new Google_Service_Calendar($this->client);
            $channel = new \Google_Service_Calendar_Channel($calender);
            $channel->setResourceId($resourceId);
            $channel->setId($channelId);

            return $calender->channels->stop($channel);
        } catch (\Google_Service_Exception $e) {
            if ($e->getCode() == 404) {
                return;
            }
            Log::error('Calendar calendar stop watch :' . getErrorDetail($e));
        } catch (\Exception $e) {
            Log::error('Calendar calendar stop watch :' . getErrorDetail($e));
            return false;
        }
    }

    public function insert($calenderId, $accessToken, $eventData, $startDateTime, $endDateTime, $attendeesList = [], $userId = null, $customEventId = null)
    {

        try {
            //conver the dates to Carbon Instance..
            $timezone = Settings::get('TIME_ZONE');
            if (!$startDateTime instanceof Carbon) {
                $startDateTime = new Carbon($startDateTime);
            }
            if (!$endDateTime instanceof Carbon) {
                $endDateTime = new Carbon($endDateTime);
            }
            $this->client->setAccessToken($accessToken);
            $calender = new Google_Service_Calendar($this->client);

            /* Adding the Event Using Injected $input variables */
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary($eventData['title']);
            $event->setDescription($eventData['description']);
            $event->setId($eventData['google_event_id']);

            if ($customEventId) {
                $event->setId($customEventId);
            }

            $start = new \Google_Service_Calendar_EventDateTime();
            $end = new \Google_Service_Calendar_EventDateTime();

            if (ine($eventData, 'full_day')) {
                $date = \Carbon\Carbon::parse($startDateTime)->format('Y-m-d');
                $start->setDate($date);
                $end->setDate($date);
            } else {
                $start->setTimeZone($timezone);
                $end->setTimeZone($timezone);
                $start->setDateTime($startDateTime->toRfc3339String());
                $end->setDateTime($endDateTime->toRfc3339String());
            }
            $event->setStart($start);
            $event->setEnd($end);
            if (ine($eventData, 'location')) {
                $event->setLocation($eventData['location']);
            }

            $attendees = [];
            foreach ($attendeesList as $key => $attendee) {
                $attendee1 = new \Google_Service_Calendar_EventAttendee();
                $attendee1->setEmail($attendee);
                $attendees[$key] = $attendee1;
            }

            $event->attendees = $attendees;

            if ($eventData->isRecurring()) {
                $service = App::make(RecurrService::class);
                $rule = $service->getGoogleRecurringRule($eventData);
                $event->setRecurrence($rule);
            }

            // if(!$eventData->reminders->isEmpty()) {
            //  $event = $this->setEventReminders($event, $eventData->reminders);
            // }

            $optParams = [
                'sendNotifications' => true,
            ];

            $event = $calender->events->insert($calenderId, $event, $optParams);

            if (!$eventId = $event->getId()) {
                return false;
            }

            return $eventId;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }
    }

    public function eventDelete($calenderId, $accessToken, $eventId, $userId = null)
    {
        try {
            $this->client->setAccessToken($accessToken);
            $calender = new Google_Service_Calendar($this->client);
            $event = $calender->events->get($calenderId, $eventId);
            if ($event->getStatus() == 'cancelled') {
                return true;
            }

            $calender->events->delete($calenderId, $eventId);
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } elseif ($e->getCode() == 404) {
                return false;
            } else {
                throw $e;
            }
        }
    }

    public function getEventById($calendarId, $eventId, $accessToken)
    {
        $this->client->setAccessToken($accessToken);

        try {
            $calender = new Google_Service_Calendar($this->client);
            $event = $calender->events->get($calendarId, $eventId);
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                return false;
            }

            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }

        return $event;
    }

    public function update($calenderId, $eventId, $accessToken, $eventData, $startDateTime, $endDateTime, $attendeesList = [], $userId = null, $customEventId = null)
    {

        try {
            $isEventExist = null;
            //conver the dates to Carbon Instance..
            $timezone = Settings::get('TIME_ZONE');
            if (!$startDateTime instanceof Carbon) {
                $startDateTime = new Carbon($startDateTime);
            }
            if (!$endDateTime instanceof Carbon) {
                $endDateTime = new Carbon($endDateTime);
            }
            $this->client->setAccessToken($accessToken);
            $calender = new Google_Service_Calendar($this->client);

            if ($eventId) {
                $isEventExist = $calender->events->get($calenderId, $eventId);
            }

            /* Adding the Event Using Injected $input variables */
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary($eventData['title']);
            $event->setDescription($eventData['description']);

            if (!$isEventExist && $customEventId) {
                $event->setId($customEventId);
            }
            $start = new \Google_Service_Calendar_EventDateTime();
            $end = new \Google_Service_Calendar_EventDateTime();

            if (ine($eventData, 'full_day')) {
                $date = \Carbon\Carbon::parse($startDateTime)->format('Y-m-d');
                $start->setDate($date);
                $end->setDate($date);
            } else {
                $start->setTimeZone($timezone);
                $end->setTimeZone($timezone);
                $start->setDateTime($startDateTime->toRfc3339String());
                $end->setDateTime($endDateTime->toRfc3339String());
            }
            $event->setStart($start);
            $event->setEnd($end);
            if (ine($eventData, 'location')) {
                $event->setLocation($eventData['location']);
            }

            $attendees = [];
            foreach ($attendeesList as $key => $attendee) {
                $attendee1 = new \Google_Service_Calendar_EventAttendee();
                $attendee1->setEmail($attendee);
                $attendees[$key] = $attendee1;
            }

            $event->attendees = $attendees;

            if ($eventData->isRecurring()) {
                $service = App::make(RecurrService::class);
                $rule = $service->getGoogleRecurringRule($eventData);
                $event->setRecurrence($rule);
            }

            // if(!$eventData->reminders->isEmpty()) {
            //  $event = $this->setEventReminders($event, $eventData->reminders);
            // }

            $optParams = [
                'sendNotifications' => true,
            ];

            if ($isEventExist) {
                $event = $calender->events->update($calenderId, $eventId, $event);
            } else {
                $event = $calender->events->insert($calenderId, $event, $optParams);
            }

            if (!$eventId = $event->getId()) {
                return false;
            }

            return $eventId;
        } catch (\Exception $e) {
            if ($this->isTokenExpireException($e)) {
                $this->fireTokenExpireEvent($accessToken);
            } else {
                throw $e;
            }
        }
    }

    private function setEventReminders($event, $reminders)
    {
        foreach($reminders as $key => $reminder) {
            $data[] = [
                'method'    => $reminder->type,
                'minutes'   => $reminder->minutes,
            ];
        }
        $eventReminder = new \Google_Service_Calendar_EventReminders();
        $eventReminder->setOverrides($data);
        $eventReminder->setUseDefault(false);
        $event->setReminders($eventReminder);
        return $event;
    }
}
