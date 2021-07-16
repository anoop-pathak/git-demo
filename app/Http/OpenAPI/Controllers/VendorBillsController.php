<?php
namespace App\Http\OpenAPI\Controllers;

use App\Http\OpenAPI\Transformers\VendorBillsTransformer;
use App\Repositories\VendorBillRepository;
use Sorskod\Larasponse\Larasponse;
use Request;
use App\Http\Controllers\ApiController;
use App\Models\Job;
use App\Models\ApiResponse;
use App\Repositories\JobRepository;

class VendorBillsController extends ApiController
{

	/**
	 * Vendor Repo
	 * @var \App\Repositories\VendorBillRepository
	 */
	protected $repo;
	
	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	protected $response;

	public function __construct(Larasponse $response, VendorBillRepository $repo, JobRepository $jobRepo)
	{
		$this->repo     = $repo;
		$this->response = $response;
		$this->jobRepo = $jobRepo;
		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes')); 
		}
	}

	public function index($jobId) 
	{
		$input = Request::all();

		$job = $this->jobRepo->findById($jobId);

		$input['job_id'] = $jobId;

		$vendorBills = $this->repo->getFilteredVendors($input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$vendorBills = $vendorBills->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($vendorBills, new VendorBillsTransformer));
	}
}