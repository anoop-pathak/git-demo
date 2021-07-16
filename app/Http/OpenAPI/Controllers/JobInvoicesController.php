<?php

namespace App\Http\OpenAPI\Controllers;

use App\Http\OpenAPI\Transformers\JobInvoiceTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Services\JobInvoices\JobInvoiceService;
use App\Http\Controllers\ApiController;
use Request;
use Validator;
use App\Models\Job;
use App\Models\ApiResponse;
use App\Repositories\JobRepository;

class JobInvoicesController extends ApiController
{

	function __construct(Larasponse $response, JobInvoiceService $invoiceService, JobRepository $jobRepo) {
		$this->response = $response;
		$this->invoiceService = $invoiceService;
		$this->jobRepo = $jobRepo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	 * Get job invoice
	 * Get jobs/{job_id}/invoices
	 * @param  int $jobId job id
	 * @return response
	 */
	public function getJobInvoices($jobId)
	{
		$input = Request::all();
		$validator = Validator::make($input, ['status' => 'In:closed,open,all']);
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->findById($jobId);
		$input['job_id'] = $jobId;

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$invoices = $this->invoiceService->getOpenApiFilteredInvoice($input);

		$invoices = $invoices->paginate($limit);

		return ApiResponse::success(
			$this->response->paginatedCollection($invoices, new JobInvoiceTransformer)
		);
	}
}