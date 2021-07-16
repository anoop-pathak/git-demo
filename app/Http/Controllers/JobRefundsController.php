<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\Refunds\JobRefundService;
use App\Repositories\JobRefundRepository;
use App\Events\RefundCreated;
use App\Events\RefundCancelled;
use App\Transformers\JobRefundTransformer;
use App\Exceptions\MinRefundAmountException;
use Request;
use App\Models\ApiResponse;
use App\Models\JobRefund;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Exception;
use FlySystem;

class JobRefundsController extends ApiController
{

	public function __construct(Larasponse $response, JobRefundService $service, JobRefundRepository $repo)
	{
		$this->repo 	= $repo;
		$this->service 	= $service;
		$this->response = $response;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	 * Display a listing of the refund receipt.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();

		$jobRefunds = $this->repo->getFilteredJobRefund($input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
			$jobRefunds = $jobRefunds->get();

			return ApiResponse::success($this->response->collection($jobRefunds, new JobRefundTransformer));
		}
		$jobRefunds = $jobRefunds->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($jobRefunds, new JobRefundTransformer));
	}

	/**
	 * Display the refund receipt.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$jobCredit = $this->repo->getById($id);

		return ApiResponse::success([
			'data' => $this->response->item($jobCredit, new JobRefundTransformer)
		]);
	}

	/**
	 * Add refund receipt
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();

		$validator = Validator::make($input, JobRefund::getCreateRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$customerId = $input['customer_id'];
			$jobId = $input['job_id'];
			$accountId = $input['financial_account_id'];
			$lines = $input['lines'];

			$jobRefund   = $this->service->createJobRefund($customerId, $jobId, $accountId, $lines, $input);
		} catch(MinRefundAmountException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

		Event::fire('JobProgress.Refunds.Events.RefundCreated', new RefundCreated($jobRefund));

		return ApiResponse::success([
			'message'     =>  trans('response.success.saved', ['attribute' => 'Job Refund']),
			'data' => $this->response->item($jobRefund, new JobRefundTransformer)
		]);
	}

	/**
	 * cancel refund receipt.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function cancel($id)
	{
		$input = Request::all();

		$jobRefund = $this->repo->getById($id);

		$validator = Validator::make($input, JobRefund::getCancelRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$this->service->cancelJobRefund($jobRefund, $input);
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

		Event::fire('JobProgress.Refunds.Events.RefundCancelled', new RefundCancelled($jobRefund));

		return ApiResponse::success([
			'message'   => trans('response.success.canceled', ['attribute' => 'Job Refund']),
		]);
	}

	/**
	 * get job refund note pdf
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getPdfPrint($id)
	{
		$jobCredit = $this->repo->getById($id);

		try {
			$path = $jobCredit->file_path;

			$headers = [
				'Content-Type'          => 'application/pdf',
			    'Content-Disposition'   => 'filename="job-refund-receipt.pdf"'
			];

			$fileResource = FlySystem::read($path);

			return 	response($fileResource, 200, $headers);
		} catch (Exception $e) {
			$errorDetail = $e->getLine() .' ' .$e->getFile() . ' '.$e->getMessage();

			return view('error-page', [
				'errorDetail' => $errorDetail,
				'message'	  => trans('response.error.error_page'),
			]);
		}
	}

	/************************ PRIVATE METHOD *******************/
}
