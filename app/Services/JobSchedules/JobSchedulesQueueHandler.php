<?php

namespace App\Services\JobSchedules;

use App\Models\User;
use App\Repositories\JobSchedulesRepository;
use App\Services\Google\GoogleCalenderServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Crypt;

class JobSchedulesQueueHandler
{
    protected $repo;
    protected $calendarService;


    /**
     * Class Constructor
     * @param    $repo
     * @param    $calendarService
     */
    public function __construct(JobSchedulesRepository $repo, GoogleCalenderServices $calendarService)
    {
        $this->repo = $repo;
        $this->calendarService = $calendarService;
    }

    /**
     * sync schedule to google calendar using queue
     * @param  $job
     * @param  $data
     * @return
     */
    public function syncToGoogleCalendar($job, $data)
    {
        $job->delete();

        try {
            if (!$this->setCompanyScope($data)) {
                return false;
            }

            $schedule = $this->repo->getFirstRecurringSchedule($data['schedule_id']);

            if (!$schedule) {
                return false;
            }

            $this->addToGoogleCalendar($schedule);
        } catch (\Exception $e) {
            $message = '';

            if (isset($schedule)) {
                $message = 'Job Schedule Id : ' . $schedule->id . " ";
            }

            $message .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Job schedule queue handler sync to google calendar: ' . $message);
        }
    }

    /**
     * remove job schedule from google calendar
     * @param  $job
     * @param  $data
     * @return
     */
    public function removeFromGoogleCalendar($job, $data)
    {
        $job->delete();

        if (!$this->setCompanyScope($data)) {
            return false;
        }

        $schedule = null;
        try {
            $schedule = $this->repo->getFirstRecurringSchedule($data['schedule_id'], $withTrashed = true);

            if (ine($data, 'only_this') && $schedule->isRecurring()) {
                $this->editGoogleCalendarSchedule($schedule);

                return true;
            }

            if (!ine($data, 'google_event_reps')) {
                return false;
            }

            $this->deleteFromGoogleCalendar($data['google_event_reps']);
        } catch (\Exception $e) {
            $message = '';

            if (isset($schedule)) {
                $message = 'Job Schedule Id : ' . $schedule->id . " ";
            }

            $message .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Job schedule queue handler remove from google calendar: ' . $message);
        }
    }

    /**
     * update schedule on google calendar
     * @param  $job
     * @param  $data
     * @return
     */
    public function updateGoogleCalendar($job, $data)
    {
        $job->delete();

        if (!$this->setCompanyScope($data)) {
            return false;
        }

        $reps = [];
        $schedule = null;
        $oldSchedule = null;
        $deletedReps = [];

        try {
            if (ine($data, 'schedule_id')) {
                $schedule = $this->repo->getFirstRecurringSchedule($data['schedule_id'], $withTrashed = true);
            }

            if (ine($data, 'old_schedule_id')) {
                $oldSchedule = $this->repo->getFirstRecurringSchedule($data['old_schedule_id'], $withTrashed = true);

                $this->editGoogleCalendarSchedule($oldSchedule);
                $this->addToGoogleCalendar($schedule);
            } else {
                $this->editGoogleCalendarSchedule($schedule);
            }


            if (ine($data, 'old_google_event_reps')) {
                $reps = $data['old_google_event_reps'];
                $deletedReps = array_diff($reps, $schedule->reps->pluck('id')->toArray());
                $this->deleteFromGoogleCalendar($deletedReps);
            }
        } catch (\Exception $e) {
            $message = '';

            if (isset($schedule)) {
                $message = 'Job Schedule Id : ' . $schedule->id . " ";
            }

            $message .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Job schedule queue handler update google calendar: ' . $message);
        }
    }

    /**
     * move schedule to other date on google calendar
     * @param   $job
     * @param   $data
     * @return
     */
    public function moveScheduleOnGoogleCalendar($job, $data)
    {
        $job->delete();

        try {
            if (!$this->setCompanyScope($data)) {
                return false;
            }

            $oldSchedule = $this->repo->getFirstRecurringSchedule($data['old_schedule_id'], $withTrashed = true);

            $schedule = $this->repo->getFirstRecurringSchedule($data['schedule_id'], $withTrashed = true);

            if ($oldSchedule) {
                if ($oldSchedule->isRecurring()) {
                    $this->editGoogleCalendarSchedule($oldSchedule);
                } else {
                    $oldGoogleEventReps = $oldSchedule->reps()->whereNotNull('google_event_id')
                        ->pluck('rep_id', 'google_event_id')->toArray();
                    $this->deleteFromGoogleCalendar($oldGoogleEventReps);
                }
            }

            $this->addToGoogleCalendar($schedule);
        } catch (\Exception $e) {
            $message = '';

            if (isset($schedule)) {
                $message = 'Job Schedule Id : ' . $schedule->id . " ";
            }

            $message .= $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Job schedule queue handler move schedule on google calendar: ' . $message);
        }
    }

    /**
     * add schedule to google calendar
     * @param $scheduleId
     */
    private function addToGoogleCalendar($schedule)
    {
        $startDate = new Carbon($schedule->start_date_time);
        $endDate = new Carbon($schedule->end_date_time);

        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays) {
            $endDate = $endDate->subDay();
        }

        $description = '';
        $reps = $schedule->reps;
        $userIds = $reps->pluck('id')->toArray();
        $userIds = arry_fu($userIds);

        $repNames = implode(',', $reps->pluck('full_name')->toArray());
        $subContractors = $schedule->subContractors;

        if ($schedule->description) {
            $description = $schedule->description . "\n\n";
        }
        if ($repNames) {
            $description .= "<b>Company Crew :</b> " . $repNames;
        }
        if ($subContractors) {
            $description .= "\n\n <b>Sub Contractor :</b> " . implode(',', $subContractors->pluck('full_name')->toArray());
        }

        $schedule['description'] = $description;

        foreach ($userIds as $key => $userId) {
            $user = User::find($userId);

            if (($user) && ($googleClient = $user->googleCalendarClient)) {
                $calenderId = $googleClient->calender_id;
                $accessToken = $googleClient->token;

                $customEventId = 'schedule' . generateUniqueToken();

                $eventId = $this->calendarService->insert($calenderId, $accessToken, $schedule, $startDate, $endDate, [], $googleClient->user_id, $customEventId);

                $schedule->reps()->where('rep_id', $user->id)
                    ->where('schedule_id', $schedule->id)
                    ->update(['google_event_id' => $eventId]);
            }
        }
    }

    /**
     * delete schedule from google calendar
     * @param  $scheduleId [id of schedule]
     * @return
     */
    private function deleteFromGoogleCalendar($reps)
    {
        if (empty($reps)) {
            return false;
        }

        foreach ($reps as $googleEventId => $rep) {
            $user = User::has('googleCalendarClient')->find($rep);
            if (!$user) {
                continue;
            }

            $this->calendarService->eventDelete(
                $user->googleClient->calender_id,
                $user->googleClient->token,
                $googleEventId,
                $user->googleClient->user_id
            );
        }
    }

    /**
     * edit schedule on google calendar
     * @param  [object] $schedule
     * @return
     */
    private function editGoogleCalendarSchedule($schedule)
    {

        $startDate = new Carbon($schedule->start_date_time);
        $endDate = new Carbon($schedule->end_date_time);

        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays) {
            $endDate = $endDate->subDay();
        }

        $description = '';
        $userIds = $schedule->reps->pluck('id')->toArray();
        $userIds = arry_fu($userIds);

        $repNames = implode(',', $schedule->reps->pluck('full_name')->toArray());
        $subContractors = $schedule->subContractors;

        if ($schedule->description) {
            $description = $schedule->description . "\n\n";
        }
        if ($repNames) {
            $description .= "<b>Company Crew :</b> " . $repNames;
        }
        if ($subContractors) {
            $description .= "\n\n <b>Sub Contractor :</b> " . implode(',', $subContractors->pluck('full_name')->toArray());
        }

        $schedule['description'] = $description;

        foreach ($userIds as $key => $userId) {
            $user = User::find($userId);

            if (($user) && ($googleClient = $user->googleCalendarClient)) {
                $calenderId = $googleClient->calender_id;
                $accessToken = $googleClient->token;
                $googleEventId = null;

                $customEventId = 'schedule' . generateUniqueToken();

                $googleEvent = DB::table('job_rep')
                    ->where('rep_id', $user->id)
                    ->where('schedule_id', $schedule->id)
                    ->first();

                if ($googleEvent) {
                    $googleEventId = $googleEvent->google_event_id;
                }

                $eventId = $this->calendarService->update($calenderId, $googleEventId, $accessToken, $schedule, $startDate, $endDate, [], $googleClient->user_id, $customEventId);
                DB::table('job_rep')
                    ->where('rep_id', $user->id)
                    ->where('schedule_id', $schedule->id)
                    ->update(['google_event_id' => $eventId]);
            }
        }
    }

    /**
     * set company scope and user login
     * @param $data
     */
    private function setCompanyScope($data)
    {
        $user = User::find(Crypt::decrypt($data['current_user_id']));

        if (!$user) {
            return false;
        }

        // login user
        \Auth::guard('web')->login($user);

        // set company scope
        setScopeId($user->company_id);

        return true;
    }
}
