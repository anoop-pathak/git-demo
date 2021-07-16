<?php

namespace App\Services\Reports;

use App\Models\FinancialDetail;
use App\Models\MarketSourceSpent;
use App\Models\Job;
use App\Models\Referral;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Transformers\MarketSourceReportTransformer;
use Sorskod\Larasponse\Larasponse;
use Config;
use DB;
use App\Models\Worksheet;

class MarketingSourceReport extends AbstractReport
{

    protected $scope;
    protected $jobRepo;

    function __construct(Context $scope, JobRepository $jobRepo, Larasponse $response)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->response =  $response;
    }

    /**
     * return data for marketing source report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        //set date filters
        $filters = $this->setDateFilter($filters);
        $data = [];

        $referralData = $this->getDataByQuery($filters);
        $data = $this->response->collection($referralData->get(), new MarketSourceReportTransformer);
        $data['customers'] = $this->getByOtherTypes($filters, 'customer');
        $data['website'] = $this->getByOtherTypes($filters, 'website');
        $data['others'] = $this->getByOtherTypes($filters, 'other');

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    /* Private Functions */

    /**
     * apply other referral filter
     *
     * @param $filters (array)
     * @return $count(array)
     */
    private function getByOtherTypes($filters, $type)
    {
        //apply other referral filter

        $filters['referred_type'] = $type;
        $totalLeads = $this->getTotalLeadsCount($filters);
        $closedLeads = $this->getClosedLeads($filters);

        $closedLeadsCount = $closedLeads->count();

        $totalJobAmount = $closedLeads->sum('total_amount');
        //averge job price..
        $avgJobPrice = 0;
        if ($closedLeadsCount) {
            $avgJobPrice = $closedLeads->sum('total_job_amount') / $closedLeadsCount;
        }

        $badLeads = $this->getBadLeadsCount($filters);

        $closingRate = 0;
        if (($totalLeads != 0) && ($closedLeadsCount != 0)) {
            $closingRate = ($closedLeadsCount / $totalLeads) * 100;
        }

        $avgProfit = 0;
        if ($closedLeadsCount) {
            $profit = $this->getAvgProfit($closedLeads);
            $avgProfit = $profit / $closedLeadsCount;
        }
        $count['total_jobs_amount'] = $totalJobAmount;
        $count['total_leads'] = $totalLeads;
        $count['leads_closed'] = $closedLeadsCount;
        $count['bad_leads'] = $badLeads;
        $count['closing_rate'] = number_format($closingRate, 2, '.', '');
        $count['avg_job_price'] = number_format($avgJobPrice, 2, '.', '');
        $count['cost'] = 0;
        $count['cost_per_lead'] = 0;
        $count['cost_per_win'] = 0;
        $count['avg_profit'] = number_format($avgProfit, 2, '.', '');

        return $count;
    }

    /**
     * apply referral filter with referral id
     *
     * @param $filters (array)
     * @return $count(array)
     */
    private function getReferralsData($filters)
    {
        //apply referral filter with referral id
        $referrals = Referral::whereCompanyId($this->scope->id())
            ->select('id', 'name')
            ->with([
                'marketSourceSpent' => function ($query) use ($filters) {
                    $query->dateRange($filters['start_date'], $filters['end_date']);
                }
            ])->get();

        $data = [];
        $filters['referred_type'] = 'referral';

        foreach ($referrals as $referral) {
            $filters['referred_by'] = $referral->id;

            $totalLeads = $this->getTotalLeadsCount($filters);
            $closedLeads = $this->getClosedLeads($filters);
            $closedLeadsCount = $closedLeads->count();

            $avgProfit = 0;
            if ($closedLeadsCount) {
                $profit = $this->getAvgProfit($closedLeads);
                $avgProfit = $profit / $closedLeadsCount;
            }

            //averge job price..
            $avgJobPrice = 0;

            $totalJobAmount = $closedLeads->sum('total_amount');
            if ($closedLeadsCount) {
                $avgJobPrice = $closedLeads->sum('total_job_amount') / $closedLeadsCount;
            }

            $badLeads = $this->getBadLeadsCount($filters);

            $closingRate = 0;
            if (($totalLeads != 0) && ($closedLeadsCount != 0)) {
                $closingRate = ($closedLeadsCount / $totalLeads) * 100;
            }
            $totalCost = $referral->marketSourceSpent->sum('amount');

            $costPerLead = 0;
            if ($closedLeadsCount && $totalCost) {
                $costPerLead = $totalCost / $totalLeads;
            }

            $costPerWin = 0;
            if ($closedLeadsCount && $totalCost) {
                $costPerWin = $totalCost / $closedLeadsCount;
            }
            $data[$referral->name]['total_jobs_amount'] = $totalJobAmount;
            $data[$referral->name]['total_leads'] = $totalLeads;
            $data[$referral->name]['leads_closed'] = $closedLeadsCount;
            $data[$referral->name]['bad_leads'] = $badLeads;
            $data[$referral->name]['closing_rate'] = number_format($closingRate, 2, '.', '');
            $data[$referral->name]['cost'] = number_format($totalCost, 2, '.', '');
            $data[$referral->name]['cost_per_lead'] = number_format($costPerLead, 2, '.', '');
            $data[$referral->name]['cost_per_win'] = number_format($costPerWin, 2, '.', '');
            $data[$referral->name]['avg_job_price'] = number_format($avgJobPrice, 2, '.', '');
            $data[$referral->name]['avg_profit'] = number_format($avgProfit, 2, '.', '');
        }

        return $data;
    }

    private function getTotalLeadsCount($filters)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters,['customers', 'awarded_stage'])->groupBy('jobs.id');
        return $jobs->get(['jobs.id'])->count();
    }

    private function getJobs($filters)
    {
        $filters['include_financial_details'] = true;

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters,['customers','financial_calculation', 'awarded_stage'])->groupBy('jobs.id');

        return $jobs->select('jobs.id', 'total_amount')
            ->get();
    }

    private function getClosedLeads($filters)
    {
        $filters['include_financial_details'] = true;

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'financial_calculation', 'awarded_stage'])->groupBy('jobs.id');

        return $jobs->closedJobs()
            // ->leftJoin(\DB::raw("(select job_id, id as worksheet_id from worksheets where type = 'profit_loss') as pl_worksheet"), 'pl_worksheet.job_id', '=', 'jobs.id')
            ->select(
                'jobs.id',
                // 'pl_worksheet.worksheet_id',
                'total_amount',
                'total_commission',
                'total_job_amount'
            )
            ->get();
    }

    private function getBadLeadsCount($filters)
    {
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'awarded_stage'])->groupBy('jobs.id');
        return $jobs->badLeads()->get(['jobs.id'])->count();
    }

    private function getAvgProfit($closedJobs)
    {
        $totalJobAmmount = $closedJobs->sum('total_amount');
        $totalCommission = $closedJobs->sum('total_commission');
        // $ids = $closedJobs->pluck('worksheet_id')->toArray();
        $jobsIds = $closedJobs->pluck('id')->toArray();

        $worksheetIds = Job::whereIn('id', $jobsIds)
            ->orWhereIn('parent_id', $jobsIds)
            ->leftJoin(DB::raw("(select job_id, id as worksheet_id from worksheets where type = 'profit_loss') as pl_worksheet"), 'pl_worksheet.job_id', '=', 'jobs.id')
            ->select('jobs.id', 'pl_worksheet.worksheet_id')
            ->pluck('worksheet_id')->toArray();


        $costToJob = FinancialDetail::whereIn('worksheet_id', $worksheetIds)
            ->leftJoin('worksheets', 'worksheets.id', '=', 'financial_details.worksheet_id')
            ->groupBy('worksheets.enable_actual_cost')
            ->selectRaw('IF(worksheets.enable_actual_cost = 1, (SUM(actual_quantity * actual_unit_cost)), (SUM(quantity * unit_cost))) as cost')
            ->get()->sum('cost');

        $totalCostToDoJob = $costToJob + $totalCommission;
        return ($totalJobAmmount - $totalCostToDoJob);
    }

    private function getDataByQuery($filters) {
        $referrals = Referral::where('referrals.company_id', $this->scope->id());
        $referralsIds = $referrals->pluck('id')->toArray();
        # for calculated Market Spent Of referrals #
        $marketSourceSpent = MarketSourceSpent::whereIn('market_source_spents.referral_id', $referralsIds)
                ->where('market_source_spents.company_id', $this->scope->id())
                ->select(DB::raw('SUM(market_source_spents.amount) as spent_amount'), 'market_source_spents.referral_id')
                ->groupBy('market_source_spents.referral_id');
        $marketSourceSpentQuery = generateQueryWithBindings($marketSourceSpent);
        # Jobs Query Builder #
        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'awarded_stage']);
        $jobs->select('jobs.*', 'customers.referred_by')->groupBy('jobs.id');
        $jobsQuery = generateQueryWithBindings($jobs);
        
        #For Bad leads
        $badLeadJobs = $this->jobRepo->getJobsQueryBuilder($filters, ['customers', 'awarded_stage']);
        $badLeadJobs->select('jobs.*')->groupBy('jobs.id');
        $badLeadJobs = $badLeadJobs->badLeads();
        $badLeadJobsQuery = generateQueryWithBindings($badLeadJobs);
        #For Awarded Jobs
        $awardedJobs =$this->jobRepo->getJobsQueryBuilder($filters, ['financial_calculation', 'customers']);
        $awardedJobs = $awardedJobs->closedJobs();
        $awardedJobs = $awardedJobs->select('job_financial_calculations.*')->groupBy('jobs.id');
        $awardedJobsIds = $awardedJobs->pluck('job_id')->toArray();
        $awardedJobsQuery = generateQueryWithBindings($awardedJobs);
        # For Calculate Average Profit of Closed or Awarded jobs #
        $worksheetIds = Worksheet::whereIn('worksheets.job_id', $awardedJobsIds)->whereType('profit_loss')->groupBy('job_id')->pluck('id')->toArray();
        $costToJob = FinancialDetail::whereIn('worksheet_id', $worksheetIds)
                        ->leftJoin('worksheets', 'worksheets.id', '=', 'financial_details.worksheet_id')
                        ->groupBy('worksheets.id')
                        ->selectRaw('IF(worksheets.enable_actual_cost = 1, (SUM(actual_quantity * actual_unit_cost)), (SUM(quantity * unit_cost))) as job_cost, worksheets.job_id as worksheets_job_id');
        $costToJobQuery = generateQueryWithBindings($costToJob);
        # Add joins on referrals #
        $referralData = $referrals->leftJoin(DB::raw("({$marketSourceSpentQuery}) as market_spents"), 'market_spents.referral_id', '=', 'referrals.id')
                        ->leftJoin(DB::raw("({$jobsQuery}) as jb"), 'jb.referred_by', '=', 'referrals.id')
                        ->leftJoin(DB::raw("({$awardedJobsQuery}) as awarded_jobs "), 'awarded_jobs.job_id', '=', 'jb.id')
                        ->leftJoin(DB::raw("({$badLeadJobsQuery}) as bad_lead_jobs"), 'bad_lead_jobs.id', '=', 'jb.id')
                        ->leftJoin(DB::raw("({$costToJobQuery}) as pl_worksheets"), 'pl_worksheets.worksheets_job_id', '=', 'awarded_jobs.job_id')
                        ->groupBy('referrals.id')
                        ->select(DB::raw("referrals.name,
                            referrals.id,
                            IFNULL(market_spents.spent_amount,0) as market_cost,
                            COUNT(jb.id) as total_leads,
                            IFNULL(SUM(pl_worksheets.job_cost), 0) as job_cost,
                            IFNULL(SUM(awarded_jobs.total_amount), 0) as awarded_total_job_amount,
                            COUNT(bad_lead_jobs.id) as bad_leads,
                            IFNULL(SUM(awarded_jobs.total_commission), 0) as awarded_total_commission,
                            COUNT(awarded_jobs.job_id) as closed_leads,
                            ((IFNULL(SUM(awarded_jobs.total_amount), 0) - (IFNULL(SUM(pl_worksheets.job_cost), 0) + IFNULL(SUM(awarded_jobs.total_commission), 0))) / COUNT(awarded_jobs.job_id))as avg_profit,
                            (IFNULL(SUM(awarded_jobs.total_job_amount), 0)/COUNT(awarded_jobs.job_id)) as avg_job_price,
                            (IFNULL(market_spents.spent_amount, 0) / COUNT(jb.id)) as cost_per_lead,
                            (IFNULL(market_spents.spent_amount, 0) / COUNT(awarded_jobs.job_id)) as cost_per_win,
                            (COUNT(awarded_jobs.job_id)/COUNT(jb.id) *100) as closing_rate
                        "));
            return $referralData;
    }
}
