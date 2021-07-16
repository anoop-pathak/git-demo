<?php

namespace App\Services\Reports;

use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Transformers\ProfitLostAnalysisReportTransformer;
use App\Transformers\ProfitLostAnalysisReportTotalTransformer;;
use Request;
use Sorskod\Larasponse\Larasponse;
use Excel;
use App\Services\Contexts\Context;

class ProfitLossAnalysisReport extends OwedToCompanyReport
{
    protected $scope;
    protected $jobRepo;
    protected $userRepo;

    function __construct(JobRepository $jobRepo, Larasponse $response, Context $scope)
    {
        $this->jobRepo = $jobRepo;
        $this->response = $response;
        $this->scope = $scope;

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
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();
        $filters = $this->setDateFilter($filters);

        $limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
        $joins = ['awarded_stage', 'financial_calculation'];

        $jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);
        $this->applyFilters($jobs, $filters);
        $jobs->awarded();
        $jobs->select('jobs.id', 'number', 'jobs.alt_id', 'jobs.multi_job', 'created_date', 'total_change_order_amount', 'total_job_amount', 'total_amount', 'pl_sheet_total', 'customer_id', 'pending_payment', 'total_received_payemnt', 'total_commission', 'total_refunds', 'total_credits');
        $with = $this->includeData($filters);
        $jobs->with($with);
        // $jobs->groupBy('jobs.id');
        $jobs->orderBy('id', 'desc');

        // export the csv file of Profit Loss Analysis Report
        if (ine($filters, 'csv_export')) {
            return $this->csvExport($jobs->get());
        }

        if (!$limit) {
            $jobs = $jobs->get();
            $data = $this->response->collection($jobs, new ProfitLostAnalysisReportTransformer);
        }else{
            $jobs = $jobs->paginate($limit);
            $data = $this->response->paginatedCollection($jobs, new ProfitLostAnalysisReportTransformer);
        }

        if($filters['duration'] == 'since_inception') {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        return $data;
    }

    /**
	 * return data for sales performace report total
	 *
	 * @param $filters(array)
	 * @return $data(array)
	 */
	public function getTotal($filters = array())
	{
		$jobAwardedStage = $this->getJobAwardedStage();

		$filters = $this->setDateFilter($filters);
		$joins = ['awarded_stage', 'financial_calculation'];

		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);

		$this->applyFilters($jobs, $filters);
		$jobs->awarded();

		$jobs = $jobs->selectRaw(
				'sum(total_job_amount) as job_price_total,
				 sum(total_change_order_amount) as change_order_amount_total,
				 sum(total_amount) as total_job_price_total,
				 sum(total_received_payemnt) as amount_received_total,
				 sum(pending_payment) as amount_owed_total,
				 sum(pl_sheet_total) as job_cost_total,
				 sum(total_commission) as sales_commission_total,
				 sum(total_refunds) as total_refunds,
				 sum(total_credits) as total_credits,
				 sum((total_amount - pl_sheet_total - total_commission)) as job_net')->first();

        return $this->response->item($jobs, new ProfitLostAnalysisReportTotalTransformer);
	}

    /**
     * export csv of Profit Loss Analysis Report
     * @param $jobs
     */
    private function csvExport($jobs)
    {

        $report = $this->response->collection($jobs, function ($jobs) {
            $profitMargin = 0;

            $jobNet = $jobs->total_amount - $jobs->pl_sheet_total - $jobs->total_commission;
            if ((float)$jobs->pending_payment) {
                $profitMargin = ($jobNet * 100) / $jobs->pending_payment;
            }
            $data = [
                'Customer Name' => $jobs->customer->full_name,
                'Job Id' => $jobs->number,
                'Job Start Date' => $jobs->created_date,
                'Total Job Price' => numberFormat($jobs->total_job_amount),
                'Change Order' => numberFormat($jobs->total_change_order_amount),
                'Total' => numberFormat($jobs->total_amount),
                'Payment Received' => numberFormat($jobs->total_received_payemnt),
                'Amount Owed' => numberFormat($jobs->pending_payment),
                'Job Costs' => numberFormat($jobs->pl_sheet_total),
                'Total Credits'		=> numberFormat($jobs->total_credits),
                'Total Refunds'		=> numberFormat($jobs->total_refunds),
                'Sales Commissions'	=> numberFormat($jobs->total_commission),
                'Job Net'			=> numberFormat($jobNet),
                'Profit Margin'		=> numberFormat($profitMargin),
            ];

            return $data;
        });

        Excel::create('Profit_Loss_Report', function ($excel) use ($report) {
            $excel->sheet('sheet1', function ($sheet) use ($report) {
                $sheet->fromArray($report['data']);
            });
        })->export('csv');
    }

    private function includeData($input)
    {
        $with = ['customer.address', 'customer.address.country', 'customer.address.state'];

        if (!isset($input['includes'])) {
            return $with;
        }

        if (in_array('customer.rep', $input['includes'])) {
            $with[] = 'customer.rep.profile';
        }

        if (in_array('estimators', $input['includes'])) {
            $with[] = 'estimators.profile';
        }

        return $with;
    }
}
