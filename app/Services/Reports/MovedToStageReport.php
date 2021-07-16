<?php

namespace App\Services\Reports;

use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Transformers\MovedToStageTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use Settings;
use Excel;

class MovedToStageReport extends AbstractReport
{
    protected $scope;
    protected $jobRepo;

    function __construct(Context $scope, JobRepository $jobRepo, Larasponse $response)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * return data for sales performace report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

        //check JOB_AWARDED_STAGE is set
        $this->getJobAwardedStage();

        //set date filters
        $filters = $this->setDateFilter($filters);
        $joins = [];

        if (ine($filters, 'job_city')) {
            $joins[] = 'address';
        }

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);

        //eager loading
        $jobs->with([
            'customer' => function ($query) {
                $query->select(
                    'id',
                    'first_name',
                    'last_name',
                    'company_name',
                    'email',
                    'is_commercial',
                    'additional_emails'
                );
            },
            'trades',
            'address',
            'address.state',
            'address.country',
        ]);

        //select job columns
        $jobs->select('jobs.id', 'number', 'jobs.name', 'alt_id', 'archived', 'multi_job', 'customer_id', 'address_id', 'amount', 'tax_rate', 'taxable', 'division_code');

        //apply move to stage filter
        if (ine($filters, 'moved_to_stage')) {
            $startDate = ine($filters, 'start_date') ? $filters['start_date'] : null;
            $endDate = ine($filters, 'end_date') ? $filters['end_date'] : null;
            $stageCode = $filters['moved_to_stage'];
            $jobs->movedToStage($stageCode, $startDate, $endDate);
            $jobs->addSelect('stage_start_date', 'stage');
        }

        // export the csv file of stage entered report
        if (ine($filters, 'csv_export')) {
            return $this->csvExport($jobs->get());
        }

        if (!$limit) {
            $jobs = $jobs->get();
            $data = $this->response->collection($jobs, new MovedToStageTransformer);
        } else {
            $jobs = $jobs->paginate($limit);
            $data = $this->response->paginatedCollection($jobs, new MovedToStageTransformer);
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    /**
     * export csv of stage entered report
     * @param $jobs
     */
    private function csvExport($jobs)
    {
        $stageReport = $this->response->collection($jobs, function ($job) {

            $data = [
                'Customer Name' => $job->customer->full_name,
                'Total Job Price'  => showAmount(totalAmount($job->amount, $job->tax_rate)),
                'Job Id' => $job->number,
                'Job #' => $job->alt_id,
                'Current Stage' => $job->getCurrentStage()['name'],
                'Stage Entered Date' => convertTimezone($job->stage_start_date, Settings::get('TIME_ZONE'))
                    ->toDateString(),
                'Trades' => implode(', ', $job->trades->pluck('name')->toArray()),
                'City' => $job->address->city
            ];

            return $data;
        });

        Excel::create('Stage_Entered_Report', function ($excel) use ($stageReport) {
            $excel->sheet('sheet1', function ($sheet) use ($stageReport) {
                $sheet->fromArray($stageReport['data']);
            });
        })->export('csv');
    }
}
