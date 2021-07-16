<?php
namespace App\Services\Reports;

use App\Services\Contexts\Context;
use App\Repositories\JobRepository;
use App\Transformers\ProjectSourceReportTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;

class ProjectSourceReport extends AbstractReport{
 	protected $scope;
	protected $jobRepo;
	protected $response;
     function __construct(Context $scope, JobRepository $jobRepo, Larasponse $response)
    {
		$this->scope = $scope;
		$this->jobRepo = $jobRepo;
		$this->response =  $response;
	}
 	/**
	 * return data for marketing source report
	 *
	 * @param $filters(array)
	 * @return $data(array)
	 */
	public function get($filters = array())
	{
		//set date filters
		$filters = $this->setDateFilter($filters);
 		$referraldata = $this->getDataByQuery($filters);
 		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
		$data = [];
		if($limit && !ine($filters, 'csv_export')) {
			$data = $this->response->paginatedCollection($referraldata->paginate($limit), new ProjectSourceReportTransformer);
		}else {
			$data = $this->response->collection($referraldata->get(), new ProjectSourceReportTransformer);
		}
		// //check JOB_AWARDED_STAGE is set
		// $jobAwardedStage = $this->getJobAwardedStage();
 		if(!ine($filters, 'page') || $filters['page'] <= 1) {
			$data['customers'] = $this->getByOtherTypes($filters, 'customer');
			$data['website']   = $this->getByOtherTypes($filters, 'website');
			$data['others']    = $this->getByOtherTypes($filters, 'other');
		}
 		// $data['data'] = $this->getReferralsData($filters);
 		return $data;
	}
 	/* Private Functions */
 	/**
	 * apply other referral filter
	 *
	 * @param $filters(array)
	 * @return $count(array)
	 */
	private function getByOtherTypes($filters, $type)
	{
		//apply other referral filter
		$data = [];
		$totalJobs = 0;
		$filters['referred_type'] = 'all';
		$totalJobs = $this->getTotalLeadsCount($filters);
		$filters['referred_type'] = $type;
		$data = $this->getData($filters);
		$totalRate = 0;
		if(($totalJobs != 0) && ($data['total_leads'] != 0)) {
			$totalRate = ($data['total_leads']/$totalJobs)*100;
		}
		$data['total_rate'] = numberFormat($totalRate);
 		return $data;
	}
 	// /**
	//  * apply referral filter with referral id
	//  *
	//  * @param $filters(array)
	//  * @return $count(array)
	//  */
	// private function getReferralsData($filters)
	// {
	// 	//apply referral filter with referral id
	// 	$referrals = \Referral::whereCompanyId($this->scope->id())
	// 		->select('id', 'name')
	// 		->get();
 	// 	$data 						= [];
	// 	$totalJobs 					= $this->getTotalLeadsCount($filters);
	// 	$filters['referred_type'] 	= 'referral';
 	// 	foreach($referrals as $referral) {
 	// 		$filters['referred_by'] = $referral->id;
	// 		$referralData = $this->getData($filters);
	// 		$totalRate = 0;
	// 		if(($totalJobs != 0) && ($referralData['total_bid_proposal_jobs'] != 0)) {
	// 			$totalRate = ($referralData['total_bid_proposal_jobs']/$totalJobs)*100;
	// 		}
	// 		$referralData['total_rate'] = $totalRate;
 	// 		$data[$referral->name]= $referralData;
	// 	}
 	// 	return $data;
	// }
 	private function getData($filters) {
		$data = [];
 		if(!ine($filters, 'referred_type')){
			return $data;
		}
 		$totalLeads 			= $this->getTotalLeadsCount($filters);
		$totalBids 				= $this->getTotalBids($filters);
		$totalBidsCount 		= $totalBids->count();
		$closedLeads 			= $this->getClosedLeads($filters);
		$closedLeadsCount 		= $closedLeads->count();
		$toalBidsAmount 		= $totalBids->sum('total_job_amount');
		$closedJobAmount 		= $closedLeads->sum('total_job_amount');
		$closingRate 			= 0;
 		$closingRate = 0;
		if(($totalLeads != 0) && ($closedLeadsCount != 0)) {
			$closingRate = ($closedLeadsCount/$totalLeads)*100;
		}
 		$data['total_leads']  			= $totalLeads;
		$data['total_bid_proposal_jobs'] = $totalBidsCount;
		$data['total_bid_proposal_job_amount'] = $toalBidsAmount;
		$data['total_awarded_jobs'] = $closedLeadsCount;
		$data['total_awarded_jobs_amount']  = $closedJobAmount;
		$data['closing_rate'] =  numberFormat($closingRate);
 		return $data;
	}
 	private function getTotalLeadsCount($filters)
	{
		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers']);
 		return $jobs->distinct('jobs.id')->count();
	}
 	private function getTotalBids($filters)
	{
		$filters['include_financial_details'] = true;
		$filters['for_bid_proposal'] = true;
		$jobs =$this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'financial_calculation']);
 		return $jobs->select('jobs.id', 'total_amount', 'total_job_amount')
			->groupBy('jobs.id')
			->get();
	}
 	private function getClosedLeads($filters)
	{
		$filters['include_financial_details'] = true;
 		$jobs =$this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'financial_calculation']);
 		return $jobs->closedJobs()
			->groupBy('jobs.id')
			->select('jobs.id','total_job_amount')
			->get();
	}
 	private function getDataByQuery($filters) {
 		$filters['referred_type'] = 'all';
		$totalJobs = $this->getTotalLeadsCount($filters);
		unset($filters['referred_type']);
		$referrals = Referral::where('referrals.company_id', $this->scope->id());
 		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers'])
		->select('jobs.*', 'customers.referred_by');
		$jobsQuery = generateQueryWithBindings($jobs);
 		#For Bid Proposal Jobs
		$filters['for_bid_proposal'] = true;
		$bidProposalJobs =$this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation'])
		->select('job_financial_calculations.*');
		$bidProposalJobsQuery = generateQueryWithBindings($bidProposalJobs);
		unset($filters['for_bid_proposal']);
 		#For Awarded Jobs
		$awardedJobs =$this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation'])
		->select('job_financial_calculations.*');
		$awardedJobs = $awardedJobs->closedJobs();
		$awardedJobsQuery = generateQueryWithBindings($awardedJobs);
 		$referralData = $referrals->leftJoin(DB::raw("({$jobsQuery}) as jb"), 'jb.referred_by', '=', 'referrals.id')
						->leftJoin(DB::raw("({$bidProposalJobsQuery}) as proposal_jobs "), 'proposal_jobs.job_id', '=', 'jb.id')
						->leftJoin(DB::raw("({$awardedJobsQuery}) as awarded_jobs "), 'awarded_jobs.job_id', '=', 'jb.id')
						->groupBy('referrals.id')
						->select(DB::raw("referrals.name,
							referrals.id,
							COUNT(jb.id) as total_leads,
							IFNULL(SUM(proposal_jobs.total_job_amount), 0) as total_bid_proposal_job_amount,
							COUNT(proposal_jobs.job_id) as total_bid_proposal_jobs,
							IFNULL(SUM(awarded_jobs.total_job_amount), 0) as total_awarded_jobs_amount,
							COUNT(awarded_jobs.job_id) as total_awarded_jobs,
							(COUNT(awarded_jobs.job_id)/COUNT(jb.id) *100) as closing_rate,
							(COUNT(jb.id)/'$totalJobs' *100) as total_rate
						"));
			return $referralData;
	}
 }