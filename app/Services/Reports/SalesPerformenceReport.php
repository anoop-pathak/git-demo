<?php

namespace App\Services\Reports;

use App\Models\Appointment;
use App\Models\AppointmentResultOption;
use App\Models\JobFollowUp;
use App\Models\Proposal;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Services\Contexts\Context;
use App\Models\JobCommission;
use Sorskod\Larasponse\Larasponse;
use Excel;
use App\Transformers\SalesPerformanceReportTransformer;
use Illuminate\Support\Facades\DB;

class SalesPerformenceReport extends AbstractReport
{
    protected $scope;
    protected $jobRepo;
    protected $userRepo;
    protected $response;

    function __construct(Context $scope, JobRepository $jobRepo, UserRepository $userRepo, Larasponse $response)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->userRepo = $userRepo;
        $this->response = $response;
    }

    /**
     * return data for sales performace report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        //get users
        $users = $this->userRepo->getFilteredUsers($filters)->get();

        //set date filters
        $filters = $this->setDateFilter($filters);

        $data = $this->getDataByQuery($filters);

		if (!isset($filters['limit']) || ine($filters, 'csv_export')) {
			$limit = 0;
		}else {
			$limit = $filters['limit'];
		}

		if($limit) {
			$data = $this->response->paginatedCollection($data->paginate($limit), new SalesPerformanceReportTransformer);
		}else {
			$data = $this->response->collection($data->get(), new SalesPerformanceReportTransformer);
		}

		if($filters['duration'] == 'since_inception') {
			$data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
		}

		if(ine($filters, 'csv_export')) {
		 	return $this->salesPerformanceCsvExport($data['data'], $filters);
		}

		return $data;
    }

    public function getBySalesman($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        //get users
        // $user = $this->userRepo->getById($userId);

        //set date filters
        $filters = $this->setDateFilter($filters);

        $data = [];

        // //count total jobs of user
        // $totalLeads = $this->getTotalLeadsCount($filters);

        //get total jobs count
        $totalJobsCount = $this->getTotalJobsCount($filters);

        //get total proposal sent
        $totalProposalSent = $this->getTotalProposalSentCount($filters);

        //get closed jobs of user
        $closedLeads = $this->getClosedLeads($filters);

		$totalJobAwardedCount = $this->getTotalAwardedJobs($filters);

        //amount of closed jobs
        $closedLeadsAmount = $closedLeads->sum('total_job_amount');

        $totalChangeOrderAmount = $closedLeads->sum('total_change_order_amount');

        //count of closed jobs
        $closedLeadsCount = $closedLeads->count();

        //count closed jobs of user
        $lostJobs = $this->getLostJobsCount($filters);

        //Get total linked appointment count
        $totalAppointments = Appointment::users((array)$filters['user_id'])
            ->recurring()
            ->dateRange($filters['start_date'], $filters['end_date'])
            ->leftJoin('appointment_result_options', 'appointment_result_options.id', '=', 'appointments.result_option_id')
            ->groupBy('appointments.result_option_id')
            ->selectRaw('appointment_result_options.name, count(appointment_recurrings.id) as count, appointment_result_options.active')
            ->get();

        $totalAppointmentsCount = $totalAppointments->sum('count');

        foreach ($totalAppointments as $key => $value) {
            $resultData[$value->name]['count']  = $value->count;
            $resultData[$value->name]['active'] = (bool)$value->active;
        }

        $resultOptions = AppointmentResultOption::where('company_id', getScopeId())->pluck('active', 'name')->toArray();

        foreach ($resultOptions as $name => $active) {
            if(isset($resultData[$name])) continue;
            $resultData[$name]['count']  = 0;
            $resultData[$name]['active'] = (bool)$active;
        }

        unset($resultData[""]['active']);
        $resultData['no_result'] = isset($resultData[""]) ? $resultData[""] : ['count' => 0];
        unset($resultData[""]);

        //get job linked appointment
        $jobLinkedAppointmentCount = Appointment::users((array)$filters['user_id'])
            ->recurring()
            ->dateRange($filters['start_date'], $filters['end_date'])
            ->has('jobs')
            ->count();

        // commissions for jobs..
        $commission = 0;
        $closedLeadsIds = $closedLeads->pluck('id')->toArray();
        if ($closedLeadsIds) {
            $commission = $commission = JobCommission::whereIn('user_id', (array)$filters['user_id'])
                ->excludeCanceled()
                ->dateRange($filters['start_date'], $filters['end_date'])
                ->jobs($closedLeadsIds)
                ->get()
                ->sum('amount');
        }

        // value per leads ..
        $vpls = 0;
        $commissionVpl = 0;
        $closedRatio = 0;
        if ($closedLeadsCount) {
            $vpls = $closedLeadsAmount / $closedLeadsCount;
            $commissionVpl = $commission / $closedLeadsCount;
        }

        //calculate closed ratio
        if ($totalJobsCount) {
            $closedRatio = $closedLeadsCount / $totalJobsCount;
        }

        $followUps = $this->getFollowUpCount($closedLeadsIds, $filters);
        $totalLostJobCount = $this->getTotalLostsJobCount($filters);
        $totalClosedJobCount = $this->getTotalClosedJobCount($filters);

        $data['data']['leads_closed_in_duration'] = $closedLeadsCount; //according to awarded date
        $data['data']['lost_jobs_in_duration']  = $lostJobs; //according to job lost date(Follow Up)
        $data['data']['total_leads_closed']     = $totalClosedJobCount; //according to job created date
        $data['data']['total_lost_jobs'] = $totalLostJobCount; //according to job created date
        $data['data']['total_jobs']      = $totalJobsCount; //according to job created date
        $data['data']['close_ratio']     = $closedRatio;
        // $data['data']['closing_rate'] = $closingRate;
        $data['data']['amount']       = $closedLeadsAmount; //according to awarded date
        $data['data']['appointments'] = $totalAppointmentsCount;
        $data['data']['job_linked_appointment_count'] = $jobLinkedAppointmentCount;
        $data['data']['appointments_without_job'] = $totalAppointmentsCount - $jobLinkedAppointmentCount;
        $data['data']['vpls']           = $vpls;
        $data['data']['commission_vpl'] = $commissionVpl;
        $data['data']['jobs_won'] = $totalJobAwardedCount;
        $data['data']['total_commission'] = $commission;
        $data['data']['follow_ups']     = $followUps;
        $data['data']['proposal_sent']  = $totalProposalSent; // according to job created date
        $data['data']['appointment_results']  = $resultData;
        $data['data']['change_order_amount'] = $totalChangeOrderAmount;
        $data['data']['total_amount'] = $closedLeadsAmount + $totalChangeOrderAmount;

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        if(ine($filters, 'csv_export')) {
            return $this->getSalesManCsvExport($data);
        }

        return $data;
    }

    /***** Private Functions *****/

    /**
     * apply filters on sales performace report
     *
     * @param $query | $filters(array)
     * @return $query
     */
    protected function applyFilters($query, $filters = [])
    {
        $includeBothUsers = true;

		//get only customer reps (default filter)
		if( ( ine($filters, 'for') 
			&& in_array('customer_rep', (array)$filters['for']) 
			&& !in_array('estimator', (array)$filters['for'])))
		{
			$includeBothUsers = false;
			$query->whereIn('jobs.customer_id', function($query) use($filters) {
				$query->select('id')->from('customers')
				->whereIn('rep_id', (array)$filters['user_id']);
			});
		}

		//get only estimators
		if( ( ine($filters, 'for')
			&& !in_array('customer_rep', (array)$filters['for'])
			&& in_array('estimator', (array)$filters['for'])))
		{
			$includeBothUsers = false;
			$query->whereIn('jobs.id', function($query) use($filters) {
				$query->selectRaw("COALESCE(jobs.parent_id, job_estimator.job_id)")
					->from('job_estimator')
					->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
					->whereIn('rep_id', (array)$filters['user_id']);
			});
		}

		// set deafult (customer_reps and estimators)
		if($includeBothUsers) {
			$query->where(function($query) use($filters) {
				$query->whereIn('jobs.customer_id', function($query) use($filters) {
					$query->select('id')->from('customers')
						->whereIn('rep_id', (array)$filters['user_id']);
				})->orWhereIn('jobs.id', function($query) use($filters) {
					$query->selectRaw("COALESCE(jobs.parent_id, job_estimator.job_id)")
						->from('job_estimator')
						->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
						->whereIn('rep_id', (array)$filters['user_id']);
				});
			});
		}
    }

    private function getTotalLeadsCount($filters)
    {
        $jobs = $this->jobRepo->getFilteredJobs($filters);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->excludeBadLeads()->get(['jobs.id'])->count();
    }

    private function getClosedLeads($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];
        // unset($filters['start_date']);
        // unset($filters['end_date']);
        // $jobs = $this->jobRepo->getFilteredJobs($filters);
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
		$jobs->select('jobs.*');
        $this->jobRepo->withFinancials($jobs);
        $jobs->closedJobs($startDate, $endDate);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);
        $jobs->select('jobs.id', 'total_job_amount', 'total_change_order_amount');

        return $jobs->get();
    }

    private function getLostJobsCount($filters)
    {
        $filters['follow_up_marks'][] = 'lost_job';
        $filters['lost_job_from'] = $filters['start_date'];
        $filters['lost_job_to'] = $filters['end_date'];
        // unset($filters['start_date']);
        // unset($filters['end_date']);
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->count();
    }

    private function getTotalJobsCount($filters)
    {
        $filters['include_lost_jobs'] = true;
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->count();
    }

    private function getFollowUpCount($userId, $filters)
    {
        $start = $filters['start_date'];
        $end = $filters['end_date'];

        $followUps = JobFollowUp::whereIn('created_by', (array)$filters['user_id'])
            ->whereActive(true)
            // ->whereIn('job_id', $jobIds)
            ->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d') >= '$start'")
            ->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d') <= '$end'")
            ->groupBy('follow_up')
            ->selectRaw("
				COUNT('id') as count, (CASE
				WHEN mark = 'call' && job_follow_up.order < 3 THEN CONCAT(mark,'',job_follow_up.order)
				WHEN mark = 'call' && job_follow_up.order >= 3 THEN CONCAT(mark,'3_or_more','')
				ELSE mark
				END) as follow_up
			")
            ->get()->toArray();

        $default = ['call1' => 0, 'call2' => 0, 'call3_or_more' => 0, 'undecided' => 0, 'lost_job' => 0, 'no_action_required' => 0];

        $followUps = array_column($followUps, 'count', 'follow_up');

        return array_merge($default, $followUps);
    }

    private function getTotalProposalSentCount($filters = [])
    {
        $filters['include_lost_jobs'] = true;
		unset($filters['exclude_parent']);
		unset($filters['projects_only']);
		$jobs = $this->jobRepo->getJobsQueryBuilder($filters);
		//get jobs in which user may custmer_rep or estimator
		$this->applyFilters($jobs, $filters);

		$totalProposals = $jobs->join('proposals', 'proposals.job_id', '=', 'jobs.id')
			->whereNull('proposals.deleted_at')
			->where('proposals.status', '!=', Proposal::DRAFT)
			->count();

		return $totalProposals;
    }

    public function getTotalLostsJobCount($filters)
    {
        $filters['follow_up_marks'][] = 'lost_job';
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->count();
    }

    public function getTotalClosedJobCount($filters)
    {
        $joins = ["awarded_stage"];
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);
        $jobs->awarded();

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->get(['jobs.id'])->count();
    }

    // /**
	//  * Get total awarded jobs
	//  * @param  array $filters  filters
	//  * @param  array  $joins   joins
	//  * @return awarded job
	//  */
	private function getTotalAwardedJobs($filters)
	{
		$jobs = $this->jobRepo->getJobsQueryBuilder($filters);
		$jobs->attachAwardedStage();
		$jobs->excludeBadLeads();
		$this->applyFilters($jobs, $filters);
		$jobs->awarded();

		return $jobs->count();
	}

	public function getSalesManCsvExport($data)
	{
		$report = $this->response->collection($data, function($data) {
			$data = [
				   'Total Jobs' 				  => $data['total_jobs'],
				   'Proposal Sent'				  => $data['proposal_sent'],
				   'Appointments (#)' 			  => $data['appointments'],
				   'Appointments With Job (#)'	  => $data['job_linked_appointment_count'],
				   'Appointments Without Job (#)' => $data['appointments_without_job'],
				   'Jobs Won (#)' 				  => $data['total_leads_closed'],
				   'Jobs Won In Duration' 		  => $data['leads_closed_in_duration'],
				   'Close Ratio In Duration' 	  => $data['close_ratio'],
				   'Lost Jobs (#)' 				  => $data['total_lost_jobs'],
				   'Lost Jobs In Duration' 		  => $data['lost_jobs_in_duration'],
				   'Amount ($)' 				  => (float)$data['total_amount'],
				   'Job Amount' 				  => (float)$data['amount'],
				   'Change Order Amount' 		  => (float)$data['change_order_amount'],
				   'Value Per Lead Sold ($)' 	  => $data['vpls'],
				   'Commission ($)' 			  => (float)$data['total_commission'],
				   'VPL (Value Per Lead)' 		  => $data['commission_vpl'],
				   'Followup 1' 				  => $data['follow_ups']['call1'],
				   'Followup 2' 				  => $data['follow_ups']['call2'],
				   'Followup 3 or more' 		  => $data['follow_ups']['call3_or_more'],
				   'No Action' 					  => $data['follow_ups']['no_action_required'],
				   'Undecided' 					  => $data['follow_ups']['undecided'],
				];

			return $data;
		});

		$sheet = Excel::create('By_Salesmen_Performance_Report', function($excel) use($report) {
			$excel->sheet('sheet1', function($sheet) use($report) {
				$sheet->fromArray($report['data']);
			});
		});

		$sheet->export('csv');
	}

	private function salesPerformanceCsvExport($data, $filters)
	{
		$report = $this->response->collection($data, function($data) {
			$data = [
				   'Sales Person' 	 => $data['full_name'],
				   'Leads Closed' 	 => $data['leads_closed'],
				   'Lost Jobs (#)' 	 => $data['lost_jobs'],
				   'Amount ($)' 	 => $data['amount'],
				];

			return $data;
		});

		$sheet = Excel::create('Sales_Performance_Report', function($excel) use($report, $filters) {
			$excel->sheet('sheet1', function($sheet) use($report, $filters) {
				$sheet->fromArray($report['data']);
			});
		});

		$sheet->export('csv');

	}

    private function getDataByQuery($filters)
	{
		$startDate = $filters['start_date'];
		$endDate = $filters['end_date'];

		$users = $this->userRepo->getUsersQueryBuilder($filters);
		$userIds = $users->pluck('id')->toArray();
		$filters['user_id'] = $userIds;

		$filters['with_archived'] = true;
		$users = $this->getFilteredJobUsers($users, $filters);

		/* Awarded Jobs */
		$awardedJobs = $this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation']);
		$awardedJobs->closedJobs($startDate, $endDate);

		$this->applyFilters($awardedJobs, $filters);
		$awardedJobs->select('job_financial_calculations.*');
		$awardedJobsQuery = generateQueryWithBindings($awardedJobs);

		/* Lost Job Filters */
		$lostJobsFilters = $filters;
		$lostJobsFilters['follow_up_marks'][] = 'lost_job';
		$lostJobsFilters['lost_job_from'] = $lostJobsFilters['start_date'];
		$lostJobsFilters['lost_job_to'] = $lostJobsFilters['end_date'];

		/* Lost Jobs */
		$lostJobs = $this->jobRepo->getJobsQueryBuilder($lostJobsFilters);
		$this->applyFilters($lostJobs, $filters);
		$lostJobs->select('jobs.id');
		$lostJobsQuery = generateQueryWithBindings($lostJobs);

		$data = $users->leftJoin(DB::raw("({$awardedJobsQuery}) as awarded_jobs "), 'awarded_jobs.job_id', '=', 'jb.job_id')
			->leftJoin(DB::raw("({$lostJobsQuery}) as lost_jobs "), 'lost_jobs.id', '=', 'jb.job_id')
			->groupBy('users.id')
			->selectRaw("
				users.id,
				users.first_name,
				users.last_name,
				IFNULL(SUM(awarded_jobs.total_job_amount), 0) as total_job_amount,
				COUNT(awarded_jobs.job_id) as total_awarded_jobs,
				COUNT(lost_jobs.id) as total_lost_jobs
			");

 		return $data;
	}

	private function getFilteredJobUsers($query, $filters)
	{
		if (!ine($filters, 'for')) {
			$filters['for'] = ['estimator', 'customer_rep'];
		}

		$filters['for'] = (array)$filters['for'];

		$join = [];

		$filters['include_lost_jobs'] = true;

		if(in_array('estimator', $filters['for'])) {
			$filters['include_projects'] = true;
			$estimatorJobQuery  = $this->jobRepo->getJobsQueryBuilder($filters)
				->join('job_estimator', 'jobs.id', '=', 'job_estimator.job_id')
				->select(DB::raw('Distinct COALESCE(jobs.parent_id, job_estimator.job_id) as job_id'), 'job_estimator.rep_id as user_id');
			$join[] = generateQueryWithBindings($estimatorJobQuery);
			unset($filters['include_projects']);
		}

		if(in_array('customer_rep', $filters['for'])) {
			$customerRepQuery 	= $this->jobRepo->getJobsQueryBuilder($filters)
				->join('customers', 'jobs.customer_id', '=', 'customers.id')
				->select('jobs.id as job_id', 'customers.rep_id as user_id');
			$join[] = generateQueryWithBindings($customerRepQuery);
		}

		$join = implode("\nUNION\n", $join);
		$query->leftJoin(DB::raw("({$join}) as jb"), 'jb.user_id', '=', 'users.id');

		return $query;
	}
}
