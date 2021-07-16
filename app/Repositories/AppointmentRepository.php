<?php

namespace App\Repositories;

use App\Helpers\SecurityCheck;
use App\Models\Appointment;
use App\Models\Attendee;
use App\Services\Contexts\Context;
use App\Services\Settings\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AppointmentRepository extends ScopedRepository
{

    /**
     * The base eloquent appointment
     * @var Eloquent
     */
    protected $model;
    protected $attendee;
    protected $scope;

    function __construct(Appointment $model, Attendee $attendee, Context $scope)
    {
        $this->model = $model;
        $this->attendee = $attendee;
        $this->scope = $scope;
    }

    /**
     * Create Appointment
     * @param  array $appointmentData appointment data
     * @param  array $attendees user ids
     * @param  array $jobIds job ids
     * @param  array $invites emails
     * @return Appointment
     */
    public function createAppointment($appointmentData, $attendees = [], $jobIds = [], $invites = [], $attachments = array())
    {
        if (ine($appointmentData, 'repeat') && !ine($appointmentData, 'series_id')) {
            $appointmentData['series_id'] = generateUniqueToken();
        }

        if ($this->scope->has()) {
            $appointmentData['company_id'] = $this->scope->id();
        }
        $appointment = null;
        if (ine($appointmentData, 'google_event_id')) {
            $appointment = Appointment::where('google_event_id', $appointmentData['google_event_id'])->first();
        }

        if (!$appointment) {
            $appointment = $this->model->create($appointmentData);
            $appointment->invites = $invites;
            $appointment->save();
        } else {
            $appointment->invites = $invites;
            $appointment->update($appointmentData);
        }

        //save attendees
        if (!empty($attendees = arry_fu((array)$attendees))) {
            $appointment->attendees()->attach($attendees);
        }

        //save longitude and latitude
        $this->attachGeoLocation($appointment);

        if (!empty($jobIds = arry_fu((array)$jobIds))) {
            $this->assignJobs($appointment, $jobIds);
        }

        if(!empty($attachments)){
			$type = Appointment::APPOINTMENT;
			$attachments = $appointment->moveAttachments($attachments);
			$appointment->saveAttachments($appointment, $type, $attachments);
		}

        return $appointment;
    }

    /**
     * Update Appointment
     * @param  Instance $appointment Appointment
     * @param  Array $appointmentData Appointment Data
     * @param  StartDateTime $startDateTime StartDateTime
     * @param  StartDateTime $endDateTime EndDateTime
     * @param  array $attendees User Ids
     * @param  array $jobIds job ids
     * @param  boolean $invites Invites
     * @return appointment
     */
    public function updateAppointment($appointment, $appointmentData, $attendees = [], $jobIds = [], $invites = false)
    {

        if (is_array($invites)) {
            $appointmentData['invites'] = $invites;
        }

        $appointmentData['series_id'] = $appointment->series_id;

        if (ine($appointmentData, 'repeat') && !ine($appointmentData, 'series_id')) {
            $appointmentData['series_id'] = generateUniqueToken();
        }

        $appointment->update($appointmentData);

        //save attendees
        $appointment->attendees()->detach();
        if (!empty($attendees = arry_fu((array)$attendees))) {
            $appointment->attendees()->attach($attendees);
        }

        //save longitude and latitude
        $this->attachGeoLocation($appointment);
        $this->assignJobs($appointment, $jobIds);

        return $appointment;
    }

    public function findByEventId($eventId, $filters = [])
    {
        $appointment = $this->make()->recurring($stopRepeating = false, $withThrashed = true)
            ->where('appointments.google_event_id', $eventId)
            ->select(['appointments.user_id', 'appointments.title', 'appointments.description', 'appointments.customer_id', 'appointments.company_id', 'appointments.location', 'appointments.google_event_id', 'appointments.lat', 'appointments.long', 'appointments.job_id', 'appointments.created_by', 'appointments.full_day', 'appointments.location_type', 'appointments.invites', 'appointments.repeat', 'appointments.occurence', 'appointments.series_id', 'appointments.created_at', 'appointments.updated_at', 'appointment_recurrings.start_date_time as start_date_time', 'appointment_recurrings.end_date_time as end_date_time', 'appointment_recurrings.deleted_by as deleted_by', 'appointments.id', 'appointment_recurrings.id as recurring_id', 'appointments.interval', 'appointments.interval', 'appointments.until_date', 'appointments.parent_id', 'until_date', 'by_day', 'exdates'])
            ->orderBy('appointment_recurrings.id', 'asc');

        if (ine($filters, 'start_date_time')) {
            $appointment->whereNotNull('repeat');
            // $datetime = \Carbon\Carbon::parse($filters['start_date_time']);
            // $datetime->setTimeZone($filters['timezone']);
            // $startDateTime = $datetime->toDateTimeString();
            $datetime = \Carbon\Carbon::parse($filters['start_date_time'], $filters['timezone']);
            $datetime->setTimeZone('UTC');
            $dateTime = $datetime->toDateTimeString();
            $appointment->where('appointment_recurrings.start_date_time', $dateTime);
        }

        if (ine($filters, 'start_date')) {
            $appointment->whereNotNull('repeat');
            $startDate = $filters['start_date'];
            $appointment->whereRaw("DATE_FORMAT(appointment_recurrings.start_date_time, '%Y-%m-%d') = '$startDate'");
        }

        if (ine($filters, 'without_recurring')) {
            $appointment->whereNull('repeat');
        }

        $appointment = $appointment->withTrashed()->first();

        if (($appointment) && $appointment->deleted_at) {
            $appointment = $appointment->restore();
        }

        return $appointment;
    }

    public function getLastRecurringAppointment($appointmentId)
    {
        return $this->make()->recurring($stopRepeating = false, $withThrashed = false, $subScope = false)
            ->where('appointment_recurrings.appointment_id', $appointmentId)
            ->orderBy('appointment_recurrings.id', 'desc')
            ->first();
    }

    public function getFirstRecurringAppointment($appointmentId, $withTrashed = false)
    {
        if ($withTrashed) {
            $appointment = $this->make()->recurring($stopRepeating = true, $withTrashed = true, $subScope = false)
                ->where('appointments.id', $appointmentId)
                ->orderBy('appointment_recurrings.id', 'asc')
                ->withTrashed()
                ->first();
        } else {
            $appointment = $this->make()->recurring($stopRepeating = false, $withThrashed = false, $subScope = false)
                ->where('appointment_recurrings.appointment_id', $appointmentId)
                ->orderBy('appointment_recurrings.id', 'asc')
                ->first();
        }

        return $appointment;
    }

    public function getLastAppointment($seriesId, $startDate = null)
    {
        $appointment = $this->make()->recurring($stopRepeating = false, $withTrashed = true, $subScope = false)
            ->where('series_id', $seriesId)
            ->where('appointment_recurrings.start_date_time', '<', $startDate)
            ->orderBy('appointment_recurrings.id', 'desc')
            ->first();

        return $appointment;
    }

    public function find($id, array $with = [], $subScope = true)
    {
        $query = $this->make($with)->recurring($stopRepeating = false, $withThrashed = false, $subScope);

        $query->where('appointment_recurrings.id', $id);

        return $query->first();
    }

    public function getFilteredAppointments($filters, $sortable = true)
    {
        $with = $this->includeData($filters);
        $appointments = $this->getAppointments($sortable, $with, $filters);

        $this->applyFilters($appointments, $filters);
        return $appointments;
    }

    public function getAppointments($sortable = true, $with = [], $input = [])
    {
        $appointments = null;

        $appointments = $this->make($with);
        if ($sortable) {
            $appointments->Sortable();
        }
        $appointments->recurring();

        $appointments->orderBy('appointment_recurrings.start_date_time', 'asc');

        if(!ine($input, 'appointment_counts_only')) {
            $appointments->groupBy('appointment_recurrings.id');
        }

        return $appointments;
    }

    /**
     * Get Nearest Date
     * @param  Array $filters Array of Filters
     * @return Response
     */
    public function getNearestDate($filters)
    {
        //find first Upcoming appointment
        $appointment = $this->getAppointments();
        $this->applyFilters($appointment, $filters);
        $appointment = $appointment->upcoming()->first();

        //find last appointment if not upcoming appointment
        if (!$appointment) {
            $appointment = $this->make()->recurring()->orderBy('start_date_time', 'desc');
            $appointment->groupBy('appointment_recurrings.id');
            $this->applyFilters($appointment, $filters);
            $appointment = $appointment->first();
        }

        if ($appointment) {
            $startDate = Carbon::parse($appointment->start_date_time)->toDateString();

            return $startDate;
        }

        return Carbon::now()->toDateString();
    }

    /** Private Functions **/

    private function assignJobs(Appointment $appointment, $jobIds)
    {
        $appointment->jobs()->detach();
        if (!empty($jobIds)) {
            sort($jobIds);
            $appointment->jobs()->attach($jobIds);
        }
        return $appointment;
    }

    private function applyFilters($query, $filters = [])
    {

        if (SecurityCheck::RestrictedWorkflow()) {
            if(Auth::user()->isStandardUser() && !in_array('view_all_user_calendars', Auth::user()->listPermissions())) {
				$filters['users'] = (array)Auth::id();
			}
        }

        if (ine($filters, 'customer_id')) {
            $query->where('appointments.customer_id', '=', $filters['customer_id']);
        }

        if (ine($filters, 'job_id')) {
            $query->jobs((array)$filters['job_id']);
        }

        if (ine($filters, 'job_ids')) {
            $query->jobs($filters['job_ids']);
        }

        if (ine($filters, 'duration')) {
            if ($filters['duration'] == 'upcoming') {
                $query->upcoming();
            } elseif($filters['duration'] == 'past') {
				$query->past();
			} elseif ($filters['duration'] == 'today') {
                $query->today();
            } elseif ($filters['duration'] == 'date') {
                $start = ine($filters, 'start_date') ? $filters['start_date'] : null;
                $end = ine($filters, 'end_date') ? $filters['end_date'] : null;
                if ($start || $end) {
                    $query->dateRange($start, $end);
                } elseif (ine($filters, 'date')) {
                    $query->date($filters['date']);
                } else {
                    $query->today();
                }
            }
        }

        // 'for' filter if when auto users-filter not applied ('for_staff_cal')
        if (ine($filters, 'for') && !ine($filters, 'for_staff_cal')) {
            if (($filters['for'] == 'users') && ine($filters, 'users')) {
                $query->users($filters['users']);
            } elseif ($filters['for'] == 'current') {
                $query->current();
            }
        }

        // 'for' filter if when auto users-filter not applied ('for_staff_cal')
        if (ine($filters, 'for_staff_cal')) {
            $users = [];
            $users = $this->getSettingUsers('ST_CAL_OPT');

            if (ine($filters, 'users')) {
                $users = $filters['users'];
            }
            $this->createSettings($users);
            $query->users($users);

            $users = [];
			$divisions = $this->getSettingUsers('STAFF_CALENDAR_DIVISION_REMINDER');

            if(ine($filters,'division_ids')) {
				$this->createDivisionSettings(arry_fu($filters['division_ids']));
			} else {
				$filters['division_ids'] = $divisions;
			}
        }

        if (ine($filters, 'created_by')) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['with_job'])) {
            if ($filters['with_job']) {
                $query->has('jobs');
            } else {
                $query->doesntHave('jobs');
            }
        }

        if(ine($filters, 'division_ids') && !empty($divisions = arry_fu($filters['division_ids']))) {
            $query->division($divisions);
        }

        if(ine($filters, 'category_ids')) {
			$query->categories($filters['category_ids']);
		}

        if(ine($filters, 'trades')){
			$query->trades($filters['trades']);
		}

        if(ine($filters, 'work_types')) {
			$query->workTypes($filters['work_types']);
		}

        if(ine($filters, 'sub_ids')) {
			$query->subContractors($filters['sub_ids']);
		}

        if(ine($filters, 'job_rep_ids')) {
			$query->reps($filters['job_rep_ids']);
		}

        if (ine($filters, 'job_flag_ids')) {
			$query->flags($filters['job_flag_ids']);
        }

		if (ine($filters, 'cities')) {
			$query->cities($filters['cities']);
		}

        // date range filters
        if((ine($filters,'start_date') || ine($filters,'end_date'))
            && ine($filters, 'date_range_type')) {
            $startDate = isSetNotEmpty($filters, 'start_date') ?: null;
            $endDate = isSetNotEmpty($filters, 'end_date') ?: null;
            switch ($filters['date_range_type']) {
                case 'appointment_created_date':
                    $query->createdDate($startDate, $endDate);
                    break;
                case 'appointment_updated_date':
                    $query->updatedDate($startDate, $endDate);
                break;
            }

        }

        if(ine($filters, 'repeat')) {
            $query->where('repeat', $filters['repeat']);
        }

        if (ine($filters, 'series_id')) {
            $query->where('series_id', $filters['series_id']);
        }

        if (ine($filters, 'without_recurring')) {
            $query->whereNull('series_id');
        }
    }

    /*
	 * @TODO make it through the queue..
	*/
    private function attachGeoLocation(Appointment $appointment)
    {

        try {
            if (!$appointment->location) {
                return false;
            }

            $location = geocode($appointment->location);
            if (!$location) {
                return false;
            }
            $appointment->lat = $location['lat'];
            $appointment->long = $location['lng'];
            $appointment->save();
        } catch (\Exception $e) {
            // No exception will be thrown here
            // echo $e->getMessage();
        }
    }

    /**
     * includeData
     * @param  Array $input | Input Array
     * @return Array
     */
    private function includeData($input = [])
    {
        $with = ['user.profile', 'attendees', 'resultOption'];

        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('attendees', $includes)) {
            $with[] = 'attendees.profile';
        }

        if (in_array('customer', $includes)) {
            // $with[] = 'customer';
            $with[] = 'customer.phones';
        }

        if (in_array('jobs', $includes)) {
            $with[] = 'jobs';
            // $with[] = 'jobs.address';
            $with[] = 'jobs.address.state';
            $with[] = 'jobs.address.country';
            $with[] = 'jobs.jobMeta';
            $with[] = 'jobs.jobWorkflow.stage';
            $with[] = 'jobs.customer.phones';
            $with[] = 'jobs.workTypes';
        }

        if(in_array('jobs.division', $includes)) {
			$with[] = 'jobs.division';
        }

		if(in_array('jobs.trades', $includes)) {
			$with[] = 'jobs.trades';
		}

        if (in_array('created_by', $includes)) {
            $with[] = 'createdBy.profile';
        }

        if(in_array('reminders', $includes)) {
            $with[] = 'reminders';
        }

        if(in_array('attachments', $includes)) {
			$with[] = 'attachments';
		}

        return $with;
    }

    /**
     * Get Setting
     * @return [type] [description]
     */
    public function getSettingUsers($key = 'ST_CAL_OPT')
    {
        $settings = new Settings();
        $setting = $settings->get($key);

        if (ine($setting, 'users')) {
            return array_unique((array)$setting['users']);
        } elseif(ine($setting,'divisions')) {
            return array_unique((array)$setting['divisions']);
        }

        return [];
    }

    public function getById($id, array $with = array(), $subScope = true)
    {
        $query = $this->make($with)->recurring($stopRepeating = false, $withThrashed = false, $subScope);

        $query->where('appointment_recurrings.id', $id);

        return $query->firstOrFail();
    }

    public function getUserRecurringAppointment($recurringId, $userId = null, $with = [])
    {
        $query = $this->make($with)->recurring();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->where('appointment_recurrings.id', $recurringId);

        return $query->first();
    }

    /**
     * Create Setting
     * @param  [type] $ids [description]
     * @return [type]      [description]
     */
    private function createSettings(array $ids = [])
    {

        $settings = App::make(\App\Repositories\SettingsRepository::class);

        $ids = !empty(array_filter($ids)) ? array_unique($ids) : (array)Auth::id();

        $data = [
            'key' => 'ST_CAL_OPT',
            'name' => 'ST_CAL_OPT',
            'user_id' => Auth::id(),
            'value' => [
                'users' => $ids
            ]
        ];

        // find settuing for user..
        $setting = $settings->getByUserId(Auth::id(), 'ST_CAL_OPT');

        if ($setting) {
            $data['id'] = $setting->id;
        }

        $settings->saveSetting($data);

        return true;
    }

    private function createDivisionSettings($ids = array())
    {
        $settings =  App::make(\App\Repositories\SettingsRepository::class);
        $data = [
            'key'     => 'STAFF_CALENDAR_DIVISION_REMINDER',
            'name'    => 'STAFF_CALENDAR_DIVISION_REMINDER',
            'user_id' => Auth::id(),
            'value'   => [
                'divisions' =>  $ids
            ]
        ];
         // find settuing for user..
        $setting = $settings->getByUserId(Auth::id(), 'STAFF_CALENDAR_DIVISION_REMINDER');

        if($setting) {
            $data['id'] =  $setting->id;
        }
         $settings->saveSetting($data);
         return true;
    }
}
