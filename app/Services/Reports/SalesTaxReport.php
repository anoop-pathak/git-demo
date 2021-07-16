<?php
namespace App\Services\Reports;

use App\Services\Reports\AbstractReport;
use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use App\Transformers\SalesTaxReportTransformer;

class SalesTaxReport extends AbstractReport
{
	protected $scope;

	function __construct(Context $scope, Larasponse $response, JobRepository $jobRepo)
	{
		$this->scope = $scope;
		$this->response = $response;
		$this->jobRepo = $jobRepo;
	}

	/**
	 * @param $filters(array)
	 * @return $data(array)
	 */
	public function get($filters = array())
	{
		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
		$filters = $this->setDateFilter($filters);
		$joins = ['customers', 'financial_calculation'];
		$filters['include_projects'] = true;
		$filters['job_project_invoices'] = true;

		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins)
			->select('jobs.*')
			->where('taxable', true);

		$jobsQuery = clone $jobs;

		$jobs->with([
			'financialCalculation',
			'customer',
			'parentJobWorkflow',
			'jobWorkflow'
		]);
		$jobs->groupBy('jobs.id');

		if(!$limit) {
			$jobs = $jobs->get();
			$data =	$this->response->collection($jobs, new SalesTaxReportTransformer);
		}else{
			$jobs = $jobs->paginate($limit);
			$data = $this->response->paginatedCollection($jobs, new SalesTaxReportTransformer);
		}

		if(ine($filters,'start_date') && (isset($filters['duration'])) && ($filters['duration'] == 'since_inception')) {
			$data['meta']['company']['created_at'] = $filters['start_date'];
		}

		$jobFinancials = $jobsQuery->selectRaw(
				'sum(jobs.amount) as job_price_without_tax,
				 sum((jobs.amount * jobs.tax_rate) / 100) as tax_amount,
				 sum(job_financial_calculations.total_job_amount) as job_price_with_tax,
				 sum(job_financial_calculations.job_invoice_amount + job_financial_calculations.job_invoice_tax_amount) as total_job_invoice_amount,
				 sum(job_financial_calculations.total_change_order_invoice_amount) as total_change_order_invoice_amount')->first();

		$data['sum']['financial_data']  = $jobFinancials;

		return $data;
	}

}