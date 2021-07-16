<?php namespace App\Repositories;

use App\Models\TimeLog;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use App\Repositories\JobRepository;

class TimeLogRepository extends ScopedRepository
{

    protected $model;
    protected $scope;
    protected $jobRepo;

    function __construct(TimeLog $model, Context $scope, JobRepository $jobRepo)
    {
        $this->scope = $scope;
        $this->model = $model;
        $this->jobRepo = $jobRepo;
    }

    /**
     * Save Job Time tracker Log
     * @param  Int $jobId Job Id
     * @param  DateTime $startDateTime Start Date Time
     * @param  int $userId User Id
     * @param  array $meta optional fields
     * @return response
     */
    public function save($startDateTime, $userId, $meta = [])
    {
        $timeLog = $this->model;
        $timeLog->company_id = $this->scope->id();
        $timeLog->job_id = ine($meta, 'job_id') ? $meta['job_id'] : null;;
        $timeLog->user_id = $userId;
        $timeLog->start_date_time = $startDateTime;
        $timeLog->clock_in_note = ine($meta, 'clock_in_note') ? $meta['clock_in_note'] : null;
        $timeLog->check_in_location = ine($meta, 'location') ? $meta['location'] : null;
        $timeLog->check_in_image = $meta['check_in_image'];
        $timeLog->lat = ine($meta, 'lat') ? $meta['lat'] : null;
        $timeLog->long = ine($meta, 'long') ? $meta['long'] : null;
        $timeLog->file_with_new_path = ine($meta, 'file_with_new_path');
        $timeLog->save();

        return $this->getById($timeLog->id);
    }

    /**
     * Get log entries
     * @param  array
     * @return queryBuilder
     */
    public function getLogEntries($filters = [])
    {
        $with = $this->getIncludeData($filters);
        $timeLogs = $this->make($with)->sortable();
        $this->applyFilters($timeLogs, $filters);

        $timeLogs->leftJoin("jobs as job", 'job.id', '=', 'timelogs.job_id')
            ->leftJoin('customers as customer', 'customer.id', '=', 'job.customer_id')
            ->join('users as user', 'user.id', '=', 'timelogs.user_id');

		$timeLogs->select(DB::raw("timelogs.*, job.customer_id, job.multi_job, job.number, job.alt_id, job.parent_id, job.name, job.multi_job,CONCAT(customer.first_name, ' ', customer.last_name) as customer_name, 
	        CONCAT(user.first_name, ' ', user.last_name) as user_name"));
        return $timeLogs;
    }

    /**
     * Get Current User Check in only
     * @param  Int $userId Integer value
     * @return response
     */
    public function getCheckInLogByUserId($userId)
    {
        return $this->make()->userCheckInLog($userId)
            ->first();
    }

    /**
     * Get by id
     * @param  int    Id
     * @param  array $with
     * @return timelog
     */
    public function getById($id, array $with = [])
    {
        $timeLog = $this->make($with)->where('timelogs.id', $id);
        $timeLog->select(DB::raw("timelogs.*, jobs.customer_id, jobs.multi_job, jobs.number, jobs.alt_id, jobs.parent_id, jobs.multi_job"));
        $timeLog->leftJoin('jobs', 'jobs.id', '=', 'timelogs.job_id');

        return $timeLog->firstOrFail();
    }

    public function getTimelogsSummary($input, $limit = null, $subGroup = null)
    {
        $entries = $this->getTimeLogEntries($input);

        //select fields from table
        $entries->selectRaw("timelogs.*, job.number as job_number,
	        CONCAT(customer.first_name, ' ', customer.last_name) as customer_name,
	        CONCAT(user.first_name, ' ', user.last_name) as user_name,
            user.email,
            user_profile.additional_phone,
	        user_profile.profile_pic, job.number, job.alt_id, job.parent_id, job.customer_id,job.multi_job,
	        COUNT(user.email) as total_entries,
	        MAX(timelogs.id) as latest_timelog,
	        DATE(timelogs.start_date_time) as timelog_start_date");

	    if(!ine($input, 'include_incomplete_entries')) {
	    	$entries->completed();
	    }

        $entries->whereNull('job.deleted_at');
        $entries->where('user.active', 1);

        // grouping..
        $groups = [
            'job' => 'timelogs.job_id',
            'user' => 'timelogs.user_id',
            'entry' => 'timelogs.start_date_time',
            'date'	=> 'timelog_start_date',
        ];

        if (isset($input['group'])) {
            $entries->addSelect(DB::raw('SUM(timelogs.duration) as duration, COUNT(timelogs.id) as total_entires'));

            if ($subGroup) {
                $entries->groupBy($groups[$input['group']], $groups[$subGroup]);
            } else {
                $entries->groupBy($groups[$input['group']]);
            }

            $group = $subGroup ? $subGroup : $input['group'];
            switch ($group) {
                case 'job':
                    $entries->addSelect('timelogs.job_id as group_id');
                    break;

                case 'user':
                    $entries->addSelect('timelogs.user_id as group_id');
                    break;

                case 'entry':
                    $entries->addSelect('timelogs.start_date_time as start_date_time');
                    break;
                //select date according to (y-m-d) thus ignore time (y-m-d h-m-s)
	        	case 'date':
	        		$entries->addSelect(DB::raw('DATE(timelogs.start_date_time) as group_id'));
	        		break;
            }
        }

        $this->applyFilters($entries, $input);

        $entries->orderBy('latest_timelog', 'DESC');

        if (!$limit) {
            $entries = $entries->get();
        } else {
            $entries = $entries->paginate($limit);
        }

        //attach sub entries
        if (isset($input['sub_group'])) {
            $this->attachSubEntries($entries, $input, $groups);
        }

        return $entries;
    }

    public function attachSubEntries($entries, &$input, $groups)
    {
        $subGroup = $input['sub_group'];
        unset($input['sub_group']);
        $subEntries = $this->getTimelogsSummary($input, $limit = 0, $subGroup);
         //group by according to start date if date is selected as group
         if($input['group'] == 'date') {
	        $subEntries = $subEntries->groupBy('timelog_start_date');
        }else {
	        $subEntries = $subEntries->groupBy(explode('.', $groups[$input['group']])[1]);
        }

        $entries->each(function ($entry) use (&$subEntries) {
            $entry->total_entries = $subEntries[$entry->group_id] ? count($subEntries[$entry->group_id]) : 0;
            $entry->sub_entries = $subEntries[$entry->group_id] ? $subEntries[$entry->group_id]->slice(0, 4) : [];
        });

        return $entries;
    }

    public function getTimeLogEntries(&$input, $sortable = true)
    {
        $entries = $this->make()
            ->leftJoin(DB::raw("jobs as job"), 'job.id', '=', 'timelogs.job_id')
            ->leftJoin('customers as customer', 'customer.id', '=', 'job.customer_id')
            ->join('users as user', 'user.id', '=', 'timelogs.user_id')
            ->join('user_profile as user_profile', 'user.id', '=', 'user_profile.user_id');

	    if($sortable){
			$entries = $entries->sortable();
		}

	    // filters
	    $this->applyFilters($entries, $input);

	    return $entries;
    }

    public function duration($filters)
	{
		$entries = $this->getTimeLogEntries($filters);
		$entries->select(DB::raw('SUM(timelogs.duration) as duration'));

		return $entries;
	}

    /************* PRIVATE**********************/

    /**
     * Eager Loading
     * @param  array $filters [description]
     * @return Array
     */
    private function getIncludeData($filters = [])
    {
        $with = [];

        $includes = isset($filters['includes']) ? $filters['includes'] : [];

        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('user', $includes)) {
            $with[] = 'user.profile';
        }

        if (in_array('customer', $includes)) {
            $with[] = 'customer';
        }

        if (in_array('job_address', $includes)) {
            $with[] = 'job.address.state';
            $with[] = 'job.address.country';
        }

        if (in_array('trades', $includes)) {
            $with[] = 'job.trades';
        }

        if(in_array('job', $includes)) {
			$with[] = 'job';
		}

        return $with;
    }

    /**
     * filters
     * @param  QueryBuilder $query
     * @param  Array $filters filters
     * @return Query Builder
     */
    private function applyFilters($query, $filters)
    {
        if (!ine($filters, 'include_incomplete_entries')) {
			$query->completed();
        }

        if (ine($filters, 'user_id')
            && (!empty($users = arry_fu((array)$filters['user_id'])))) {
            $query->whereIn('timelogs.user_id', $users);
        }

        if (ine($filters, 'job_id')) {
            $query->whereIn('timelogs.job_id', (array)$filters['job_id']);
        }

        if (ine($filters, 'date')) {
            $query->date($filters['date']);
        }

        if (ine($filters, 'start_date') || ine($filters, 'end_date')) {
            $startDate = issetRetrun($filters, 'start_date');
            $endDate = issetRetrun($filters, 'end_date');
            $query->dateRange($startDate, $endDate);
        }

        if (ine($filters, 'month')) {
            $query->month($filters['month']);
        }

        if(ine($filters, 'without_job_entries')) {
			$query->whereNull('timelogs.job_id');
		}

		if(ine($filters, 'only_job_entries')) {
			$query->whereNotNull('timelogs.job_id');
		}

		if(ine($filters, 'division_id')) {
			$query->whereIn('division_id', (array)$filters['division_id']);
		}

		if(ine($filters, 'trades')){
			$query->trades($filters['trades']);
		}

		if(ine($filters, 'work_types')){
			$query->workTypes($filters['work_types']);
		}
    }
}
