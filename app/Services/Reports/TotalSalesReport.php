<?php
namespace App\Services\Reports;

use App\Services\AbstractReport;
use App\Services\Contexts\Context;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Models\Proposal;
use App\Repositories\WorkflowRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\TotalSalesReportTransformer;
use Excel;
use Illuminate\Support\Facades\DB;
use App\Transformers\SalePerformanceJobsWonTransformer;

class TotalSalesReport extends SalesPerformenceReport
{
	protected $scope;
	protected $jobRepo;
	protected $userRepo;
	protected $response;
	function __construct(Context $scope, JobRepository $jobRepo, UserRepository $userRepo, WorkflowRepository $workflowRepo, Larasponse $response)
	{
		$this->scope = $scope;
		$this->jobRepo = $jobRepo;
		$this->userRepo = $userRepo;
		$this->workflowRepo = $workflowRepo;
		$this->response = $response;
	}
 	/**
	 * return data for sales performace report
	 *
	 * @param $filters(array)
	 * @return $data(array)
	 */
 	public function get($filters = array())
 	{
		//check JOB_AWARDED_STAGE is set
 		$jobAwardedStage = $this->getJobAwardedStage();
 		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
 		//set date filters
 		$filters = $this->setDateFilter($filters);
 		if(ine($filters, 'csv_export')) {
			set_time_limit(0);
 			$limit = 0;
 		}
 		$salesdata = $this->getDataByQuery($filters);
 		$data = [];
 		if($limit) {
 			$data = $this->response->paginatedCollection($salesdata->paginate($limit), new TotalSalesReportTransformer);
 		}else {
 			$data = $this->response->collection($salesdata->get(), new TotalSalesReportTransformer);
 		}
 		if(ine($filters, 'csv_export')) {
 			return $this->csvExport($data['data'], $filters);
 		}
 		return $data;
 	}
 	private function csvExport($data, $filters)
 	{
		$report = $this->response->collection($data, new SalePerformanceJobsWonTransformer);

		$sheet = Excel::create('Sales_Performance_Jobs_Won_Report', function($excel) use($report, $filters){
 			$excel->sheet('sheet1', function($sheet) use($report, $filters){
 				$sheet->fromArray($report['data']);
 			});
		});

		$sheet->export('csv');
 	}
 	private function getDataByQuery($filters)
 	{
 		if(ine($filters, 'sales_performance_for')){
 			$users = $this->userRepo->getUsersQueryBuilder($filters);
 			$userIds = $users->pluck('id')->toArray();
 			$filters['user_ids'] = $userIds;
 		}

 		$filters['with_archived'] = true;
 		$users = $this->getFilteredJobUsers($users,$filters);

 		#For Bid Proposal Jobs
 		$filters['for_bid_proposal'] = true;
 		$bidProposalJobs = $this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation'])
	 		->leftJoin('proposals', 'proposals.job_id', '=', 'job_financial_calculations.job_id')
			->where('proposals.status','!=',Proposal::DRAFT)
			->selectRaw('COUNT(proposals.id) as bids_proposal_count, job_financial_calculations.*')->groupBy('job_financial_calculations.job_id');
 		$bidProposalJobsQuery = generateQueryWithBindings($bidProposalJobs);
 		unset($filters['for_bid_proposal']);

 		#For Awarded Jobs
 		$awardedJobs =$this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation'])
 			->select('job_financial_calculations.*')
			->groupBy('job_financial_calculations.job_id');
 		$awardedJobs = $awardedJobs->closedJobs();
 		$awardedJobsQuery = generateQueryWithBindings($awardedJobs);

 		#for Change Orders Count
		$estimatorFilter = $filters;
		$changeOrderFilters['include_projects'] = true;
		$changeOrdersJobs = $this->jobRepo->getJobsQueryBuilder($changeOrderFilters)
			->join('change_orders', 'change_orders.job_id', '=', 'jobs.id')
			->whereNull('change_orders.canceled')
			->select(DB::raw('COALESCE(jobs.parent_id, change_orders.job_id) as job_id, COUNT(change_orders.id) as total_change_order'))
			->groupBy('job_id');
		$changeOrdersJobsQuery = generateQueryWithBindings($changeOrdersJobs);
		$salesData = $users->leftJoin(DB::raw("({$bidProposalJobsQuery}) as proposal_jobs "), 'proposal_jobs.job_id', '=', 'jb.job_id')
			->leftJoin(DB::raw("({$awardedJobsQuery}) as awarded_jobs "), 'awarded_jobs.job_id', '=', 'jb.job_id')
			->leftJoin(DB::raw("({$changeOrdersJobsQuery}) as change_orders"), 'change_orders.job_id', '=', 'awarded_jobs.job_id')
			->groupBy('users.id')
			->select(DB::raw("
				users.first_name,
				users.last_name,
				users.id,
				COUNT(jb.job_id) as total_leads,
				IFNULL(SUM(proposal_jobs.total_job_amount), 0) as total_bid_proposal_job_amount,
				COUNT(proposal_jobs.job_id) as total_bid_proposal_jobs,
				IFNULL(SUM(awarded_jobs.total_job_amount), 0) as orig_contract_amount,
				IFNULL(SUM(awarded_jobs.total_amount), 0) as revised_contract_amount,
				IFNULL(SUM(awarded_jobs.total_change_order_amount), 0) as change_order_amount,
				IFNULL(SUM(awarded_jobs.total_account_payable_amount), 0) as total_account_payable_amount,
				IFNULL(SUM(change_orders.total_change_order), 0) as total_change_orders,
				COUNT(awarded_jobs.job_id) as total_awarded_jobs,
				IFNULL(SUM(proposal_jobs.bids_proposal_count), 0) as total_bid_proposal_count
			"));

 		return $salesData;
 	}

 	private function getFilteredJobUsers($query, $filters)
	{
		$companyId = getScopeId();
		$join = [];
 		if(ine($filters, 'sales_performance_for')) {
 			if(in_array('estimator', $filters['sales_performance_for'])) {
				$estimatorFilter = $filters;
				$estimatorFilter['include_projects'] = true;
				unset($estimatorFilter['sales_performance_for']);
				$estimatorJobQuery  = $this->jobRepo->getJobsQueryBuilder($estimatorFilter)
					->join('job_estimator', 'jobs.id', '=', 'job_estimator.job_id')
					->select(DB::raw('Distinct COALESCE(jobs.parent_id, job_estimator.job_id) as job_id'), 'job_estimator.rep_id as user_id');
				$join[] = generateQueryWithBindings($estimatorJobQuery);
				unset($estimatorFilter['include_projects']);
			}
			if(in_array('customer_rep', $filters['sales_performance_for'])) {
				$customerRepQuery 	= $this->jobRepo->getJobsQueryBuilder($filters)
					->join('customers', 'jobs.customer_id', '=', 'customers.id')
					->select('jobs.id as job_id', 'customers.rep_id as user_id');
				$join[] = generateQueryWithBindings($customerRepQuery);
			}
		}
 		$join = implode("\nUNION\n", $join);
		$query->leftJoin(DB::raw("({$join}) as jb"), 'jb.user_id', '=', 'users.id');

 		return $query;
	}
 }