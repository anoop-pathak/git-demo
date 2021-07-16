<?php
namespace App\Services\Reports;

use App\Services\Reports\AbstractReport;
use App\Services\Contexts\Context;
use App\Repositories\JobInvoiceRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\InvoiceListTransformer;

class JobInvoiceListing extends AbstractReport
{

	protected $scope;
	protected $jobInvoiceRepo;
	protected $response;

    function __construct(Context $scope, JobInvoiceRepository $jobInvoiceRepo, Larasponse $response)
    {
		$this->scope   = $scope;
		$this->jobInvoiceRepo = $jobInvoiceRepo;
		$this->response = $response;
	}

	/**
	 * Get Invoice List Report Data
	 * @param  array  $filters Array of filters
	 * @return Response
	 */
	public function get($filters = array())
	{
		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

		$invoices = $this->jobInvoiceRepo->getJobInvoiceListing($filters);

		if(!$limit) {
			$invoices = $invoices->get();
			$data = $this->response->collection($invoices, new InvoiceListTransformer);
		} else {
			$invoices = $invoices->paginate($limit);
			$data = $this->response->paginatedCollection($invoices, new InvoiceListTransformer);
		}

		return $data;
	}

	/**
	 * Get Sum of Invoices
	 * @param  array  $filters [description]
	 * @return array
	 */
	public function getSumOfInvoices($filters = array())
	{
		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');

		$invoices = $this->jobInvoiceRepo->getSumOfInvoices($filters);

		$response['data'] = [
			'invoice_amount'       => numberFormat($invoices->invoice_amount),
			'invoice_tax_amount'   => numberFormat($invoices->tax_rate_amount),
			'total_invoice_amount' => numberFormat($invoices->total_invoice_amount),
			'total_invoice_open_balance' => numberFormat($invoices->open_amount),
		];

		return $response;
	}
}