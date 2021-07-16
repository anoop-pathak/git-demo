<?php

namespace App\Services\Reports;

use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Services\Contexts\Context;

class SalesPerformenceReport2 extends AbstractReport
{
    protected $scope;
    protected $jobRepo;
    protected $userRepo;

    function __construct(Context $scope, JobRepository $jobRepo, UserRepository $userRepo)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->userRepo = $userRepo;
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

        //set date filters
        $filters = $this->setDateFilter($filters);

        //get users
        $users = $this->userRepo->getUsers()->get();

        $data = [];

        foreach ($users as $key => $user) {
            //set user to get jobs
            $filters['user_id'] = $user->id;
            // //count all jobs of user
            // $allLeads = $this->getTotalLeadsCount($filters);

            // //count all jobs of user
            // $allLeadsInDateRange = $this->getTotalLeadsCount($filters, $filters['start_date'], $filters['end_date']);

            //count all jobs of user
            $allLeadsTillDate = $this->getTotalLeadsCount($filters, null, $filters['end_date']);

            //get closed jobs of user
            $closedLeadsInDateRange = $this->getClosedLeads($filters, $filters['start_date'], $filters['end_date']);

            //get closed jobs of user
            $closedLeadsTillDate = $this->getClosedLeads($filters, null, $filters['end_date']);

            //count closed jobs of user
            $lostJobsInDateRange = $this->getLostJobsCount($filters, $filters['start_date'], $filters['end_date']);

            //count closed jobs of user
            $lostJobsTillDate = $this->getLostJobsCount($filters, null, $filters['end_date']);

            $inProgress = $allLeadsTillDate - ($lostJobsTillDate + $closedLeadsTillDate);

            // report data..
            $data[$key]['full_name'] = $user->full_name;
            $data[$key]['leads_closed_in_date_range'] = $closedLeadsInDateRange;
            $data[$key]['leads_closed_till_date'] = $closedLeadsTillDate;
            $data[$key]['lost_jobs_in_date_range'] = $lostJobsInDateRange;
            $data[$key]['lost_jobs_till_date'] = $lostJobsTillDate;
            // $data[$key]['all_leads']  	= $allLeads;
            // $data[$key]['all_leads_in_date_range'] = $allLeadsInDateRange;
            $data[$key]['all_leads_till_date'] = $allLeadsTillDate;
            $data[$key]['in_progress'] = $inProgress;
        }

        // return $data;

        $report = [];
        foreach ($data as $key => $value) {
            $report['all_leads_till_date'][$value['full_name']] = $value['all_leads_till_date'];
            $report['lost_jobs_in_date_range'][$value['full_name']] = $value['lost_jobs_in_date_range'];
            $report['lost_jobs_till_date'][$value['full_name']] = $value['lost_jobs_till_date'];
            $report['leads_closed_in_date_range'][$value['full_name']] = $value['leads_closed_in_date_range'];
            $report['leads_closed_till_date'][$value['full_name']] = $value['leads_closed_till_date'];
            $report['in_progress'][$value['full_name']] = $value['in_progress'];
        }
        return $report;
    }

    /************** Private Functions ***********/

    /**
     * All jobs of a users
     */
    private function getTotalLeadsCount($filters, $startDate = null, $endDate = null)
    {
        $jobs = $this->jobRepo->getJobs();

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        // date range
        if ($startDate) {
            $jobs->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.created_date') . ", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $jobs->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.created_date') . ", '%Y-%m-%d') <= '$endDate'");
        }

        // $jobs->excludeBadLeads();
        return $jobs->get(['jobs.id'])->count();
    }

    private function getClosedLeads($filters, $startDate = null, $endDate = null)
    {
        $jobs = $this->jobRepo->getJobs();

        $jobs->closedJobs($startDate, $endDate);

        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->get(['jobs.id'])->count();
    }

    private function getLostJobsCount($filters, $startDate = null, $endDate = null)
    {
        $jobs = $this->jobRepo->getJobs();

        // $jobs->where(function($query) use($startDate, $endDate){
        $jobs->whereHas('currentFollowUpStatus', function ($query) use ($startDate, $endDate) {
            $query->where('mark', 'lost_job');

            if ($startDate) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d') >= '$startDate'");
            }

            if ($endDate) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d') <= '$endDate'");
            }
        });
        // });
        //get jobs in which user may custmer_rep or estimator
        $this->applyFilters($jobs, $filters);

        return $jobs->get(['jobs.id'])->count();
    }

    /**
     * apply filters on sales performace report
     *
     * @param $query | $filters(array)
     * @return $query
     */
    private function applyFilters($query, $filters = [])
    {
        // get both customer reps and estimators
        if (ine($filters, 'for')
            && in_array('customer_rep', (array)$filters['for'])
            && in_array('estimator', (array)$filters['for'])
        ) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('jobs.customer_id', function ($query) use ($filters) {
                    $query->select('id')->from('customers')
                        ->where('rep_id', $filters['user_id']);
                })->orWhereIn('jobs.id', function ($query) use ($filters) {
                    $query->select('job_id')->from('job_estimator')
                        ->where('rep_id', $filters['user_id']);
                });
            });
        }

        //get only customer reps (default filter)
        if ((ine($filters, 'for')
            && in_array('customer_rep', (array)$filters['for'])
            && !in_array('estimator', (array)$filters['for']))) {
            $query->whereIn('jobs.customer_id', function ($query) use ($filters) {
                $query->select('id')->from('customers')
                    ->where('rep_id', $filters['user_id']);
            });
        }

        //get only estimators
        if ((ine($filters, 'for')
            && !in_array('customer_rep', (array)$filters['for'])
            && in_array('estimator', (array)$filters['for']))) {
            $query->whereIn('jobs.id', function ($query) use ($filters) {
                $query->select('job_id')->from('job_estimator')
                    ->where('rep_id', $filters['user_id']);
            });
        }

        // set deafult (only customer_reps)
        if ((ine($filters, 'for')
                && !in_array('customer_rep', (array)$filters['for'])
                && !in_array('estimator', (array)$filters['for']))
            || (!ine($filters, 'for'))
        ) {
            $query->whereIn('jobs.customer_id', function ($query) use ($filters) {

                $query->select('id')->from('customers')
                    ->where('rep_id', $filters['user_id']);
            });
        }
    }
}
