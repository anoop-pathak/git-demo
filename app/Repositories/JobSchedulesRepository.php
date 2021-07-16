<?php

namespace App\Repositories;

use App\Models\Job;
use App\Models\JobSchedule;
use App\Services\Contexts\Context;
use App\Services\JobRepTrack;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\SubContractors\SubContractorFilesService;
use App\Exceptions\AccessForbiddenException;
use Settings;
use App\Models\Setting;
use App\Models\ScheduleRecurring;

class JobSchedulesRepository extends ScopedRepository
{

    /**
     * The base eloquent JobSchedule
     * @var Eloquent
     */
    protected $model;

    /**
     * The Current Company
     * @var [Company Object]
     */
    protected $scope;

    function __construct(JobSchedule $model, Context $scope, SubContractorFilesService $fileService)
    {
        $this->model = $model;
        $this->scope = $scope;
        $this->fileService = $fileService;
    }

    /**
     * get schedules
     *
     * @param  [array] $filters
     * @return [object] [schedule object]
     */
    public function getSchedules($filters = [])
    {
        $with = $this->includeData($filters);
        $query = $this->make($with);

        $query->Sortable();

        $query->recurring();

        return $this->applyFilters($query, $filters);
    }

    /**
     * save Schedule
     * @param  [string] $title         [title]
     * @param  [dateTime] $startDateTime [start date time]
     * @param  [dateTime] $endDateTime   [end date time]
     * @param  [int] $createdBy     [User Id]
     * @param  [string] $description   [schedule description]
     * @return [object]                [schedule object]
     */
    public function saveSchedule($title, $createdBy, $meta = [])
    {
        $jobId = $customerId = null;

        if (ine($meta, 'job_id')) {
            $job = Job::find($meta['job_id']);
            $jobId = $job->id;
            $customerId = $job->customer_id;
        }

        $model = new JobSchedule;
		$model->job_id      = $jobId;
		$model->company_id  = $this->scope->id();
		$model->title       = $title;
		$model->description =  ine($meta, 'description') ? $meta['description'] : null;
		$model->created_by    = $createdBy;
		$model->modified_by   = $createdBy;
		$model->customer_id   = $customerId;
		$model->subject_edited  = ine($meta,'subject_edited');
		$model->repeat    = ine($meta,'repeat') ? $meta['repeat'] : null;
		$model->occurence = ine($meta,'occurence') ? $meta['occurence'] : 0;
		$model->type      = ine($meta, 'type') ? $meta['type'] : JobSchedule::SCHEDULE_TYPE;
		$model->interval = ine($meta, 'interval') ? $meta['interval'] : 1;
		$model->completed_at = ine($meta, 'completed_at') ? $meta['completed_at'] : null;
		if(ine($meta, 'repeat') && !ine($meta, 'series_id')) {
			$model->series_id = generateUniqueToken();
		} elseif (ine($meta, 'series_id')) {
			$model->series_id = $meta['series_id'];
		}
		$model->full_day = ine($meta,'full_day');
		$model->save();
		$this->attachEntities($model, $meta);

		if (ine($meta,'attachments')) {
			$type = JobSchedule::SCHEDULE_TYPE;
			$attachments = $model->moveAttachments($meta['attachments']);
			$model->saveAttachments($model, $type, $attachments);

		}

        return $this->model;
    }

    /**
     * Update Schedule
     * @param  JobSchedule $schedule [description]
     * @param  [string]    $title         [description]
     * @param  [dateTime]  $startDateTime [description]
     * @param  [dateTime]  $endDataTime   [description]
     * @param  [int]       $modifiedBy    [description]
     * @param  [string]    $description   [description]
     * @return [schedule]                     [description]
     */
    public function updateSchedule(JobSchedule $schedule, $title, $modifiedBy, $meta = [])
    {
        $schedule->title = $title;
        $schedule->description = ine($meta, 'description') ? $meta['description'] : null;
        $schedule->modified_by = $modifiedBy;
        $schedule->subject_edited = ine($meta, 'subject_edited');
        $schedule->repeat = ine($meta, 'repeat') ? $meta['repeat'] : null;
        $schedule->occurence = ine($meta, 'occurence') ? $meta['occurence'] : null;
        // $schedule->series_id       = ine($meta['series_id']) ? $meta['series_id'] : null;
        $schedule->interval = ine($meta, 'interval') ? $meta['interval'] : 1;
        $schedule->full_day = ine($meta, 'full_day');
        $schedule->update();
        if ($schedule->repeat && !$schedule->series_id) {
            $schedule->series_id = ine($meta, 'series_id') ? $meta['series_id'] : generateUniqueToken();
            $schedule->update();
        }

        $schedule->detachAllEntity();

        $this->attachEntities($schedule, $meta);

        if (ine($meta, 'attachments')) {
			$type = JobSchedule::SCHEDULE_TYPE;
			$attachments = $schedule->moveAttachments($meta['attachments']);
			$schedule->updateAttachments($schedule, $type, $attachments);
		}

        return $schedule;
    }

    /**
     * apply filters on scheduled jobs
     *
     * @param $query : job object | $filters: array
     * @return $query
     */
    public function applyFilters($query, $filters = [])
    {
        $query->where(function ($query) use ($filters) {

            // schedule include
            $query->whereHas('job', function ($query) use ($filters) {
                $query->own();

                if (ine($filters, 'division_ids')) {
                    $query->whereIn('division_id', (array)$filters['division_ids']);
                }

                //flags
                if (ine($filters, 'job_flag_ids')) {
                    $query->flags($filters['job_flag_ids']);
                }

                if (ine($filters, 'city')
                    || ine($filters, 'cities')
                    || ine($filters, 'job_address')) {
                    $query->leftJoin('addresses as address', 'address.id', '=', 'jobs.address_id');

                    if (ine($filters, 'city')) {
                        $query->where('address.city', 'Like', '%' . $filters['city'] . '%');
                    }

                    // filter according multiple cities name
                    if (ine($filters, 'cities')) {
                        $query->whereIn('address.city', $filters['cities']);
                    }

                    //job address
                    if (ine($filters, 'job_address')) {
                        $query->where(DB::raw("CONCAT(address.address,' ',address.city,' ',address.zip)"), "LIKE", '%' . $filters['job_address'] . '%');
                    }
                }
            });

            //events include
            if (!ine($filters, 'division_ids') 
                && !ine($filters, 'job_flag_ids') 
                && !ine($filters, 'city')) {
                $query->orWhereNull('job_id');
            }
        });

        //job id filter
        if (ine($filters, 'job_id')) {
            $query->whereJobId($filters['job_id']);
        }

        if (ine($filters, 'customer_id')) {
            $query->whereCustomerId($filters['customer_id']);
        }

        // filter by customer name
        if (ine($filters, 'customer_name')) {
            $query->leftJoin('customers', 'customers.id', '=', 'job_schedules.customer_id')
                ->where(DB::raw('CONCAT(customers.first_name, " ", customers.last_name)'), 'Like', '%' . $filters['customer_name'] . '%');
        }

        //trade ids
        if (ine($filters, 'trades')) {
            $query->trades((array)$filters['trades']);
        }

        //work types filter
        if (ine($filters, 'work_types')) {
            $query->workTypes((array)$filters['work_types']);
        }

        //job rep ids
        if (ine($filters, 'job_rep_ids')) {
            $query->reps((array)$filters['job_rep_ids']);
        }

        //job sub contractors ids
        if (ine($filters, 'sub_ids')) {
            $query->subContractors((array)$filters['sub_ids']);
        }

        if (ine($filters, 'date')) {
            $date = $filters['date'];
            $query->date($date);
        }

        // //schedule start_date_time
        if ((ine($filters, 'start_date_time') || ine($filters, 'end_date_time')) && !ine($filters, 'date')) {
            $start = ine($filters, 'start_date_time') ? $filters['start_date_time'] : null;
            $end = ine($filters, 'end_date_time') ? $filters['end_date_time'] : null;

            $query->dateRange($start, $end);
        }

        if (ine($filters, 'type')) {
            $query->whereType($filters['type']);
        }

        //work order ids
        if (ine($filters, 'work_order_ids')) {
            $query->workOrders((array)$filters['work_order_ids']);
        }

        // material list ids
        if (ine($filters, 'material_list_ids')) {
            $query->materialLists((array)$filters['material_list_ids']);
        }

        if(ine($filters, 'category_ids')){
            $query->categories($filters['category_ids']);
        }

        return $query;
    }

    /**
     * Find an entity by id
     *
     * @param int $id
     * @param array $with
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = [])
    {
        $query = $this->make($with);
        $query->recurring();
        $query->where('schedule_recurrings.id', $id);

        return $query->firstOrFail();
    }

    public function getLastRecurringSchedule($scheduleId)
    {
        return $this->make()->recurring()
            ->where('schedule_recurrings.schedule_id', $scheduleId)
            ->orderBy('schedule_recurrings.id', 'desc')
            ->first();
    }

    public function getFirstRecurringSchedule($scheduleId, $withTrashed = false)
    {
        if ($withTrashed) {
            $appointment = $this->make()->recurring($stopRepeating = true, $withTrashed = true)
                ->where('schedule_recurrings.schedule_id', $scheduleId)
                ->orderBy('schedule_recurrings.id', 'asc')
                ->withTrashed()
                ->first();
        } else {
            $appointment = $this->make()->recurring()
                ->where('schedule_recurrings.schedule_id', $scheduleId)
                ->orderBy('schedule_recurrings.id', 'asc')
                ->first();
        }

        return $appointment;
    }

    public function find($id, array $with = [])
    {
        $query = $this->make($with)->recurring();

        $query->where('schedule_recurrings.id', $id);

        return $query->first();
    }

    /**
     * Get nearest Date
     * @param  Array $filters Filters
     * @return Date
     */
    public function getNearestDate($filters)
    {
        $query = $this->make();
        $query->recurring();
        $this->applyFilters($query, $filters);

        $scheduleQuery = clone $query;

        $schedule = $query->upcoming()->first();

        if (!$schedule) {
            $scheduleQuery->orderBy('schedule_recurrings.start_date_time', 'desc');
            $schedule = $scheduleQuery->first();
        }

        if ($schedule) {
            $startDate = Carbon::parse($schedule->start_date_time)->toDateString();

            return $startDate;
        }

        return Carbon::now()->toDateString();
    }

    private function attachEntities($schedule, $data = [])
    {
        $jobId = ($schedule->job_id) ?: 0;

        if (ine($data, 'trade_ids') && !empty($trades = arry_fu((array)$data['trade_ids']))) {
            $schedule->trades()->attach($trades, ['job_id' => $jobId]);
        }

        if (ine($data, 'work_type_ids') && !empty($workTypes = arry_fu((array)$data['work_type_ids']))) {
            $schedule->workTypes()->attach($workTypes, ['job_id' => $jobId]);
        }

        if (ine($data, 'work_crew_note_ids') && !empty($notes = arry_fu((array)$data['work_crew_note_ids']))) {
            $schedule->workCrewNotes()->attach($notes);
        }

        if (isset($data['work_order_ids'])) {
            $this->attachWorkOrdersOrMaterialLists($schedule, arry_fu((array)$data['work_order_ids']), 'work_order');
        }

        if (isset($data['material_list_ids'])) {
            $this->attachWorkOrdersOrMaterialLists($schedule, arry_fu((array)$data['material_list_ids']), 'material_list');
        }

        if(ine($data,'delete_attachments')) {
			$type = JobSchedule::SCHEDULE_TYPE;
			$schedule->deleteAttachments($schedule, $type, $data['delete_attachments']);
		}
    }

    /**
     * Attach work orders Or material lists
     * @param  Instance $schedule Schedule
     * @param  array $ids Array of work order ids Or material list ids
     * @param  type $type defines type wether Material List Or Work Order
     * @return boolean
     */
    public function attachWorkOrdersOrMaterialLists($schedule, array $ids = [], $type)
    {
        switch ($type) {
            case 'material_list':
                $schedule->materialLists()->sync($ids);
                break;

            case 'work_order':
                $schedule->workOrders()->sync($ids);
                break;
        }

        return true;
    }

    /**
     * Detach work orders
     * @param  Instance $schedule Schedule
     * @param  Array $workOrderIds Work order ids
     * @return Boolean
     */
    public function detachWorkOrders($schedule, $workOrderIds)
    {
        $schedule->workOrders()->detach($workOrderIds);

        return true;
    }

    /**
     * includeData
     * @param  Array $input | Input Array
     * @return Array
     */
    private function includeData($input = [])
    {
        $with = [
            'customer.rep.profile',
            'job' => function($query) {
				$query->select('jobs.*');
				$query->attachCurrentStage();
			},
            'job.address.state',
            'job.address.country',
            'job.division',
            'job.jobWorkflow',
            'job.jobMeta',
        ];

        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('work_orders', $includes)) {
            $with[] = 'workOrders';
        }

        if (in_array('material_lists', $includes)) {
            $with[] = 'materialLists';
        }

        if (in_array('customer.phones', $includes)) {
            $with[] = 'customer.phones';
        }

        if (in_array('customer.phones', $includes)) {
            $with[] = 'customer.phones';
        }

        if (in_array('job.parent', $includes)) {
            $with[] = 'job.parentJob';
        }

        if (in_array('trades', $includes)) {
            $with[] = 'trades';
        }

        if (in_array('reps', $includes)) {
            $with[] = 'reps.profile';
        }

        if (in_array('sub_contractors', $includes)) {
            $with[] = 'subContractors.profile';
        }

        if (in_array('work_types', $includes)) {
            $with[] = 'workTypes';
        }

        if(in_array('reminders', $includes)) {
            $with[] = 'reminders';
        }

        if(in_array('attachments', $includes)) {
			$with[] = 'attachments';
		}

        return $with;
    }

    public function  saveRepsAndSubcontractor($schedule, $meta, $data = [])
	{
		$recurrings = $schedule->recurrings;
		$oldSchedule = $schedule;
		$repIds = [];
		$subIds = [];


		if (ine($data, 'old_schedule_id')) {
			$oldSchedule = $this->getFirstRecurringSchedule($data['old_schedule_id'], $withTrashed = true);
		}

		if(ine($meta, 'rep_ids')) {
			$repIds = arry_fu((array)$meta['rep_ids']);
		}

		if(ine($meta, 'sub_contractor_ids')) {
			$subIds = arry_fu((array)$meta['sub_contractor_ids']);
		}

		foreach ($recurrings as $recurring) {
			$recurringId = $recurring->id;
			if(!empty($repIds)) {

				if ($oldSchedule->id != $schedule->id) {
					$recurringId = $meta['old_recurring_id'];
				}

				$oldRepStatus =  DB::table('job_rep')->whereIn('rep_id', $repIds)
							->where('schedule_id', $oldSchedule->id)
							->where('recurring_id', $recurringId)
							->pluck('status', 'rep_id')
                            ->toArray();

				$repSyncData = $this->getSyncData($schedule, $oldRepStatus, $repIds);

				$recurring->recurringsReps()->sync($repSyncData);
			} else {
				$repSyncData = [];
				$recurring->recurringsReps()->sync($repSyncData);
			}

			if(!empty($subIds)) {
				$oldSubStatus =  DB::table('job_sub_contractor')->whereIn('sub_contractor_id', $subIds)
					->where('schedule_id', $oldSchedule->id)
					->where('recurring_id', $recurringId)
					->pluck('status', 'sub_contractor_id')
                    ->toArray();

				$subSyncData = $this->getSyncData($schedule, $oldSubStatus, $subIds);
				$recurring->recurringsSubContractors()->sync($subSyncData);
			} else {
				$subSyncData = [];
				$recurring->recurringsSubContractors()->sync($subSyncData);
			}
		}

		if ($oldSchedule->id != $schedule->id) {
			$oldSchedule->reps()->wherePivot('recurring_id', '=', $meta['old_recurring_id'])->detach();
			$oldSchedule->subContractors()->wherePivot('recurring_id', '=', $meta['old_recurring_id'])->detach();
		}

		$job = $schedule->job;
		if($job) {
			JobRepTrack::track($job, Job::REP);
			$this->fileService->createSubDir($job, $subIds);
		}

	}

	public function updateStatus($recurringId, $authUser, $status, $onlyThis)
	{
		$schedule = $this->getById($recurringId);
		$recurring = ScheduleRecurring::findOrFail($recurringId);

		if ($schedule->type == JobSchedule::EVENT_TYPE) {
			throw new AccessForbiddenException(trans('response.error.schedule_confirmation_notification_not_allowed'));
		}
		$this->updateRepAndSubsStatus($schedule->id, array($recurringId), $authUser, $status);

		if(!$onlyThis) {
			$recurrings = $schedule->recurrings()->whereDate('start_date_time', '>', $recurring->start_date_time)->pluck('id')->toArray();

			$uniqueRecurringIds = array_diff($recurrings, array($recurringId));
			$this->updateRepAndSubsStatus($schedule->id, $uniqueRecurringIds, $authUser, $status);
		}

		return $schedule;
	}

	public function updateRepAndSubsStatus($scheduleId, $recurringIds, $authUser, $status)
	{
		$carbonNow = Carbon::now();

		DB::table('job_rep')
			->where('schedule_id', $scheduleId)
			->whereIn('recurring_id', $recurringIds)
			->where('rep_id', $authUser)
			->update([
				'status' => $status,
				'status_updated_at' => $carbonNow
			]);

		DB::table('job_sub_contractor')
			->where('schedule_id', $scheduleId)
			->whereIn('recurring_id', $recurringIds)
			->where('sub_contractor_id', $authUser)
			->update([
				'status' => $status,
				'status_updated_at' => $carbonNow
			]);
	}

	private function getSyncData($schedule, $oldStatus, $ids) {
		$carbonNow = Carbon::now()->toDateTimeString();
		$setting = Settings::get('SHOW_SCHEDULE_CONFIRMATION_STATUS');
		$status = null;
		$syncData = [];

		foreach ($ids as $id) {
			if($setting && $schedule->type == JobSchedule::SCHEDULE_TYPE){
				$status = isset($oldStatus[$id]) ? $oldStatus[$id] : JobSchedule::PENDING_STATUS;
			}

			$syncData[$id]['status']  = $status;
			$syncData[$id]['status_updated_at'] = $carbonNow;
			$syncData[$id]['job_id'] = $schedule->job_id ? $schedule->job_id : 0;
			$syncData[$id]['schedule_id'] = $schedule->id;
		}

		return $syncData;
	}
}
