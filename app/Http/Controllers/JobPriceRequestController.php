<?php
namespace App\Http\Controllers;

use App\Repositories\JobPriceRequestRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\JobPriceRequestTransformer;
use App\Exceptions\JobPriceRequetChangeStatusException;
use App\Exceptions\JobPriceRequetAmountException;
use App\Http\Controllers\ApiController;
use Request;
use Settings;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\Models\JobPriceRequest;
use App\Models\ApiResponse;
use App\Helpers\SecurityCheck;

class JobPriceRequestController extends ApiController {

	protected $repo;
	protected $response;

	public function __construct(JobPriceRequestRepository $repo,
		Larasponse $response)
	{
		$this->repo = $repo;
		$this->response = $response;
		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		try{
			if(!Settings::get('ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE')) {
				return ApiResponse::errorGeneral('Please enable job price update request submit feature.');
			}
			$input = Request::all();
			$validator = Validator::make($input, ['job_id' => 'required']);
			if($validator->fails()) {

				return ApiResponse::validation($validator);
			}
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
			$jobPriceRequest = $this->repo->getFilteredJobPrice($input['job_id'], $input);
			$jobPriceRequest = $jobPriceRequest->paginate($limit);

			return ApiResponse::success(
				$this->response->paginatedCollection($jobPriceRequest, new JobPriceRequestTransformer)
			);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		if(!Settings::get('ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE')) {
			return ApiResponse::errorGeneral('Please enable job price update request submit feature.');
		}
		$input = Request::all();
		$validator = Validator::make($input, JobPriceRequest::getRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$request = $this->repo->save($input['job_id'], $input['amount'], $input);
		}catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::success([
			'message' => trans('response.success.saved', ['attribute' => 'Job Price update request']),
			'data'    => $this->response->item($request, new JobPriceRequestTransformer)
		]);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function changeStatus($id)
	{
		if(!Settings::get('ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE')) {
			return ApiResponse::errorGeneral('Please enable job price update request submit feature.');
		}

		if(!SecurityCheck::hasPermission('approve_job_price_request')) {
			return ApiResponse::errorForbidden();
		}

		$request = $this->repo->getById($id);
		$input = Request::onlyLegacy('approve');
		$validator = Validator::make($input, JobPriceRequest::getChangeStatusRule());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$jobPriceRequest = $this->repo->changeStatus($request, $input['approve']);
			$message = 'Job price update request rejected.';
			if($input['approve']) {
				$message = 'Job price update request approved successfully.';
			}

			return ApiResponse::success([
				'message' => $message,
			]);
		} catch(JobPriceRequetChangeStatusException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		}
		catch(JobPriceRequetAmountException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		}
		 catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}