<?php

namespace App\Services\Reports;

use App\Models\Proposal;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Repositories\WorkflowRepository;
use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use Excel;
use Illuminate\Support\Facades\DB;
use App\Models\WorkflowStage;

class SalesPerformenceSummaryReport extends SalesPerformenceReport
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
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        //get users
        $users = $this->userRepo->getFilteredUsers($filters);

        if (ine($filters, 'csv_export')) {
            set_time_limit(0);
            $limit = 0;
        }

        if (!$limit) {
            $users = $users->get($limit);
        } else {
            $users = $users->paginate($limit);
        }

        //set date filters
        $filters = $this->setDateFilter($filters);

        $data = [];

        //get last stage of workflow
        $workflow = $this->workflowRepo->getActiveWorkflow(getScopeId());
        $workflowLastStage = $workflow->stages->last();
        $stageCode = $workflowLastStage->code;

        //awarded_stage for exclude bad lead jobs
        foreach ($users as $key => $user) {
            //set user to get jobs
            $filters['user_id'] = $user->id;

            $leadJob = $this->getLeadJobs($filters);


            //get closed job amount
            $closedJobsTotalAmount = $this->getLastStageJobsAmount($filters, $stageCode);

            //moved to job awarded stage
            $jobAward = $this->getTotalAwardedJobs($filters);

            $totalJobAwardedCount = $jobAward->total_awarded_job_count;

            $proposal = $this->getProposalCount($filters);

            $data['data'][$key]['id']           = $user->id;
            $data['data'][$key]['first_name']   = $user->first_name;
            $data['data'][$key]['last_name']    = $user->last_name;
            $data['data'][$key]['full_name']    = $user->full_name;
            $data['data'][$key]['full_name_mobile']  = $user->full_name_mobile;
            $data['data'][$key]['total_leads']       = $leadJob->total_leads;
            $data['data'][$key]['bids_proposal_count']  = $proposal['bids_proposal_count']; //exclude drafts
            $data['data'][$key]['bids_jobs_count']      = $proposal['bids_jobs_count'];
            $data['data'][$key]['awarded_job_count']    = $totalJobAwardedCount;
            $data['data'][$key]['contracts_jobs_count'] = $proposal['contracts_jobs_count'];
            $data['data'][$key]['contracts_proposal_count'] = $proposal['contracts_proposal_count'];
            $data['data'][$key]['bid_amount']           = $leadJob->job_amount;
            $data['data'][$key]['orig_contract_amount'] = $jobAward->total_job_amount;
            $data['data'][$key]['change_order_amount']  = $jobAward->total_change_order_amount;
            $data['data'][$key]['contract_amount']      = $jobAward->total_amount;
            $data['data'][$key]['closed_jobs_amount']   = $closedJobsTotalAmount;
            $data['data'][$key]['total_change_orders']  = $leadJob->total_change_orders;
            $data['data'][$key]['total_awarded_change_order'] = $jobAward->total_change_orders;
        }

        if(!$data){
        	$data = ['data' => $data];
        }

        if (ine($filters, 'csv_export')) {
            return $this->csvExport($data['data'], $filters);
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    /**
     * Get lead jobs
     * @param  array $filters filters
     * @param  array $joins joins
     * @return lead job
     */
    private function getLeadJobs($filters)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
        // $jobs->attachAwardedStage();
        $this->jobRepo->withFinancials($jobs);
        $this->applyFilters($jobs, $filters);

        $jobs->selectRaw('
			COUNT(jobs.id) as total_leads,
			SUM((SELECT COUNT(change_orders.id) FROM change_orders WHERE jobs.id = change_orders.job_id AND canceled IS NULL)) as total_change_orders,
			IFNULL(SUM(total_job_amount), 0) as job_amount');
        $leadJob = $jobs->first();

        return $leadJob;
    }

    /**
     * Get total awarded jobs
     * @param  array $filters filters
     * @param  array $joins joins
     * @return awarded job
     */
    private function getTotalAwardedJobs($filters)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobs->attachAwardedStage();
        $this->jobRepo->withFinancials($jobs);
        $jobs->excludeBadLeads();
        $this->applyFilters($jobs, $filters);
        $jobs->awarded();
        // $jobs->select('jobs.id');
        $jobs->selectRaw('jobs.id,
            COUNT(jobs.id) as total_awarded_job_count,
			SUM((SELECT COUNT(change_orders.id) FROM change_orders WHERE jobs.id = change_orders.job_id AND canceled IS NULL)) as total_change_orders,
			IFNULL(SUM(job_financial_calculations.total_amount), 0) as total_amount,
			IFNULL(SUM(job_financial_calculations.total_job_amount), 0) as total_job_amount,
			IFNULL(SUM(job_financial_calculations.total_change_order_amount), 0) as total_change_order_amount
			');

        return $jobs->first();
    }

    /**
     * Get last stage job amount
     * @param  array $filters filters
     * @param  string $stageCode code
     * @param  array $joins array
     * @return total amount
     */
    private function getLastStageJobsAmount($filters, $stageCode)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobs->attachAwardedStage();
        $this->jobRepo->withFinancials($jobs);
        $jobs->excludeBadLeads();
        $this->applyFilters($jobs, $filters);
        $jobs->checkStageHistory($stageCode);

        return $jobs->sum('job_financial_calculations.total_amount');
    }

    /**
     * Get proposal count
     * @param  array $filters filters
     * @return count
     */
    private function getProposalCount($filters)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters);
        $jobs->attachAwardedStage();
        $jobs->excludeBadLeads();
        $this->applyFilters($jobs, $filters);
        $jobs->select('jobs.id');
        $jobIds = $jobs->pluck('jobs.id');

        $bidProposalCount = $bidJobCount = $proposalCount = $jobCount = 0;

		if(!empty($jobIds)) {
			$draft = Proposal::DRAFT;
			$accepted = Proposal::ACCEPTED;
			$proposal = Proposal::whereIn('job_id', $jobIds)
				->selectRaw("
					COUNT(CASE WHEN proposals.status != '$draft' THEN proposals.id END) as bids_proposal_count,
					COUNT(DISTINCT(CASE WHEN proposals.status != '$draft' THEN proposals.job_id END)) AS lead_job_count,
					COUNT(CASE WHEN proposals.status = '$accepted' THEN proposals.id END) as proposal_count,
					COUNT(DISTINCT(CASE WHEN proposals.status = '$accepted' THEN proposals.job_id END)) AS job_proposal_count
				")
				->first();
			$bidProposalCount = $proposal->bids_proposal_count;
			$bidJobCount = $proposal->lead_job_count;
			$proposalCount = $proposal->proposal_count;
			$jobCount = $proposal->job_proposal_count;
		}

        return [
            'bids_proposal_count' =>  $bidProposalCount,
            'bids_jobs_count' => $bidJobCount,
            'contracts_proposal_count' => $proposalCount,
            'contracts_jobs_count' => $jobCount
        ];
    }

    private function csvExport($data, $filters)
    {
        $report = $this->response->collection($data, function ($data) {
            $data = [
                'Salesman / Customer Rep' => $data['full_name'],
                '# Leads' => $data['total_leads'],
                '# Jobs' => $data['bids_jobs_count'],
                '# Proposal' => $data['bids_proposal_count'],
                '# Jobs Awarded' => $data['awarded_job_count'],
                '# Contracts (Proposal)' => $data['contracts_proposal_count'],
                '# Contracts (Jobs)' => $data['contracts_jobs_count'],
                'Total Selling Price' => $data['bid_amount'],
                'Job Awarded Amount' => $data['orig_contract_amount'],
                'Change Order Amount ' => $data['change_order_amount'],
                'Contract Amount' => $data['contract_amount'],
                'Closed Jobs Amount ' => $data['closed_jobs_amount'],
                '# Change Orders' => $data['total_change_orders']
            ];

            return $data;
        });

        Excel::create('Sales_Summary_Report', function ($excel) use ($report, $filters) {
            $excel->sheet('sheet1', function ($sheet) use ($report, $filters) {
                $sheet->fromArray($report['data']);
            });
        })->export('csv');
    }
}
