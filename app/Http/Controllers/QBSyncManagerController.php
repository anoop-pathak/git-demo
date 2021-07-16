<?php
namespace App\Http\Controllers;

use App\Services\QuickBooks\SyncManager\Service as QBSyncManagerService;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\QBSyncCustomerTransformer;
use App\Transformers\QBSyncBatchTransformer;
use Carbon\Carbon;
use App\Transformers\Optimized\JobsTransformer;
use App\Transformers\JPSyncCustomerTransformer;
use App\Transformers\QBSyncJobTransformer;
use App\Services\QuickBooks\Exceptions\InvalidSyncCustomerException;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use Request;
use App\Models\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Models\QuickbookSyncBatch;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\SyncRequestAction;
use App\Models\QBOCustomer;
use App\Models\QuickbookMappedJob;
use Illuminate\Support\Facades\Auth;
use QuickBooks;

class QBSyncManagerController extends ApiController
{
	public function __construct(QBSyncManagerService $qbSyncManagerService, Larasponse $response)
	{
		$this->qbSyncManagerService = $qbSyncManagerService;
		$this->response = $response;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	 * save a new batch of quickbook sync process
	 * POST - quickbook/sync
	 *
	 * @return response
	 */
	public function saveQBSyncBatch()
	{
		$input = Request::all();

		$validator = Validator::make($input, QuickbookSyncBatch::getSaveQBBatchRules());
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		if(QuickbookSyncBatch::hasPendingStatus()) {

			return ApiResponse::errorGeneral("You already have a request in the syncing queue.");
		}
		// DB::beginTransaction();
		try {
			$batch = $this->qbSyncManagerService->saveQBSyncBatch($input);
			// DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Sync request']),
				'data' => $this->response->item($batch, new QBSyncBatchTransformer)
			]);
		} catch (Exception $e) {
			// DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * get quickbook sync batch listing
	 * GET - quickbook/sync
	 *
	 * @return response
	 */
	public function qbSyncBatchListing()
	{
		$input = Request::all();
		$batches = $this->qbSyncManagerService->qbSyncBatchListing($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
			$batches = $batches->get();

			return ApiResponse::success($this->response->collection($batches, new QBSyncBatchTransformer));
		}
		$batches = $batches->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($batches, new QBSyncBatchTransformer));
	}

	/**
	 * get a quickbook sync batch by id
	 * GET - quickbook/sync/{id}
	 *
	 * @param  Integer | $id | Id of a batch
	 * @return response
	 */
	public function showQBSyncBatch($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		return ApiResponse::success([
			'data' => $this->response->item($batch, new QBSyncBatchTransformer)
		]);
	}

	/**
	 * mark request of QB sync as completed
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function markBatchAsComplete($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		// if($batch->completion_date) {

		// 	return ApiResponse::errorGeneral(trans('response.error.already_completed', ['attribute' => 'Sync request']));
		// }

		$dateTime = Carbon::now()->toDateTimeString();

		$batch->completed_by = Auth::id();
		$batch->status = QuickbookSyncBatch::STATUS_CLOSED;
		$batch->completion_date = $dateTime;
		$batch->status_changed_date_time = $dateTime;
		$batch->save();

		return ApiResponse::success([
			'message' => trans('response.success.mark_as', ['attribute' => 'Sync request', 'as_attribute' => 'completed']),
			'data' => $this->response->item($batch, new QBSyncBatchTransformer)
		]);
	}

	/**
	 * get customer list which gets syned from JP to QB
	 * GET - quickbook/sync_request/{id}/jp_to_qb
	 *
	 * @param  Integer | $id | Id of sync request
	 * @return response
	 */
	public function getJpSyncCustomers($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$input['origin'] = QuickbookSyncCustomer::ORIGIN_JP;

		$customers = $this->qbSyncManagerService->getJpSyncCustomers($batch->id, $input);

		if(!$limit) {
			$customers = $customers->get();

			return ApiResponse::success($this->response->collection($customers, new JPSyncCustomerTransformer));
		}
		$customers = $customers->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($customers, new JPSyncCustomerTransformer));
	}

	/**
	 * get customer list which gets syned from QB to JP
	 * GET - quickbook/sync_request/{id}/qb_to_jp
	 *
	 * @param  Integer | $id | Id of sync request
	 * @return response
	 */
	public function getQBSyncCustomers($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$input['origin'] = QuickbookSyncCustomer::ORIGIN_QB;

		if(isset($input['keyword'])){
			$input['qb_keyword'] = $input['keyword'];
			unset($input['keyword']);
		}

		$customers = $this->qbSyncManagerService->getQBSyncCustomers($batch->id, $input);

		if(!$limit) {
			$customers = $customers->get();

			return ApiResponse::success($this->response->collection($customers, new QBSyncCustomerTransformer));
		}
		$customers = $customers->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($customers, new QBSyncCustomerTransformer));
	}

	/**
	 * get matching customers listing
	 * GET - /quickbook/sync_request/{id}/matching_customers
	 *
	 * @param  Integer | $id | ID of a SyncRequest
	 * @return response
	 */
	public function getMatchingCustomers($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$customers = $this->qbSyncManagerService->getMatchingCustomers($batch->id, $input);

		if(!$limit) {
			$customers = $customers->get();

			return ApiResponse::success($this->response->collection($customers, new JPSyncCustomerTransformer));
		}
		$customers = $customers->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($customers, new JPSyncCustomerTransformer));
	}

	/**
	 * get action required customers listing
	 * GET - /quickbook/sync_request/{id}}/action_required
	 *
	 * @param  Integer | $id | ID of a SyncRequest
	 * @return response
	 */
	public function getActionRequiredCustomers($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$customers = $this->qbSyncManagerService->getActionRequiredCustomers($batch->id, $input);

		if(!$limit) {
			$customers = $customers->get();

			return ApiResponse::success($this->response->collection($customers, new JPSyncCustomerTransformer));
		}

		$customers = $customers->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($customers, new JPSyncCustomerTransformer));
	}

	/**
	 * ignore customer
	 * PUT - quickbook/sync_request/{id}/ignore_record
	 *
	 * @param  Integer | $id | Id of Sync Request
	 * @return response
	 */
	public function ignoreCustomer($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, QuickbookSyncCustomer::ignoreOrReinstateRules());
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}
		try{
			$this->qbSyncManagerService->ignoreOrReinstateSyncCustomer($batch->id, $input['details'], $input['type'], 1);

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Customer']),
			]);

		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}


	}

	/**
	 * ignore customer
	 * PUT - quickbook/sync_request/{id}/reinstate_record
	 *
	 * @param  Integer | $id | Id of Sync Request
	 * @return response
	 */
	public function reinstateCustomer($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, QuickbookSyncCustomer::ignoreOrReinstateRules());

		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		try{
			$this->qbSyncManagerService->ignoreOrReinstateSyncCustomer($batch->id, $input['details'], $input['type'], 0);

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Customer']),
			]);

		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * get sync customer stats according to sync request id
	 * GET - quickbook/sync_request/{id}/stats
	 *
	 * @param  Integer | $id | Sync Request Id
	 * @return response
	 */
	public function getSyncCustomerStats($id)
	{
		$input = Request::all();
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		if((ine($input, 'type') && $input['type'] == 'qb_to_jp') && isset($input['keyword'])){
			$input['qb_keyword'] = $input['keyword'];
			unset($input['keyword']);

		}
		$stats = $this->qbSyncManagerService->getSyncCustomerStats($batch->id, $input);

		return ApiResponse::success([
			'data' => $stats
		]);
	}

	/**
	 * get jobs of a JP customer of sync request
	 * GET - /quickbook/sync_request/{id}/jp_to_qb/jobs
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function getJPSyncJobsOfCustomer($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, [
			'sync_customer_id' => 'required'
		]);
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['sync_customer_id'])
			->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
			->firstOrFail();

		$customer = Customer::findOrFail($syncCustomer->customer_id);
		$jobs = $this->qbSyncManagerService->getJPSyncJobsOfCustomer($customer, $input);

		$transformer = new JobsTransformer;
		$transformer->setDefaultIncludes([]);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		if(!$limit) {
			$jobs = $jobs->get();

			return ApiResponse::success($this->response->collection($jobs, $transformer));
		}
		$jobs = $jobs->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($jobs, $transformer));
	}

	/**
	 * get QB jobs of QB customer of sync request
	 * GET - /quickbook/sync_request/{id}/qb_to_jp/jobs
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function getQBSyncJobsOfCustomer($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, [
			'sync_customer_id' => 'required'
		]);
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('qb_id', $input['sync_customer_id'])
			->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
			->firstOrFail();

		$customer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jobs = $this->qbSyncManagerService->getQBSyncJobsOfCustomer($customer, $input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		if(!$limit) {
			$jobs = $jobs->get();

			return ApiResponse::success($this->response->collection($jobs, new QBSyncJobTransformer));
		}
		$jobs = $jobs->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($jobs, new QBSyncJobTransformer));
	}

	/**
	 * get jobs of a matching customer of sync request
	 * GET - /quickbook/sync_request/{id}/qb_to_jp/jobs
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function getMatchingCustomersJobs($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, [
			'sync_customer_id' => 'required',
			'sync_qb_id' => 'required',
		]);
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['sync_customer_id'])
			->where('qb_id', $input['sync_qb_id'])
			->where('action_required', false)
			->firstOrFail();

		$qbCustomer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jpCustomer = Customer::where('id', $syncCustomer->customer_id)
			->where('company_id', getScopeId())
			->firstOrFail();

		$data = $this->qbSyncManagerService->getQBAndJPCustomerJobs(
			$jpCustomer,
			$qbCustomer,
			'updated_at',
			'qb_modified_date',
			SORT_DESC,
			$input
		);

		return ApiResponse::success(['data' => $data]);
	}

	/**
	 * get jobs of a matching customer of sync request
	 * GET - /quickbook/sync_request/{id}/qb_to_jp/mapped_jobs
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function getMatchingCustomersMappedJobs($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, [
			'sync_customer_id' => 'required',
			'sync_qb_id' => 'required',
		]);
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['sync_customer_id'])
			->where('qb_id', $input['sync_qb_id'])
			->where('action_required', false)
			->firstOrFail();

		$qbCustomer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jpCustomer = Customer::where('id', $syncCustomer->customer_id)
			->where('company_id', getScopeId())
			->firstOrFail();

		$data = $this->qbSyncManagerService->getQBAndJPCustomerMappedJobs($jpCustomer, $qbCustomer, $input);

		return ApiResponse::success(['data' => $data]);
	}

	/**
	 * get jobs of a customer of sync request who is linked in JP and QB
	 * GET - /quickbook/sync_request/{id}/action_required/jobs
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function getActionRequiredCustomerJobs($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$input = Request::all();

		$validator = Validator::make($input, [
			'sync_customer_id' => 'required',
			'sync_qb_id' => 'required',
		]);
		if( $validator->fails() ){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['sync_customer_id'])
			->where('qb_id', $input['sync_qb_id'])
			->where('action_required', true)
			->firstOrFail();

		$qbCustomer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jpCustomer = Customer::where('id', $syncCustomer->customer_id)
			->where('company_id', getScopeId())
			->firstOrFail();

		$data = $this->qbSyncManagerService->getActionRequiredCustomerJobs(
			$jpCustomer,
			$qbCustomer,
			'quickbook_id',
			'qb_job_id',
			SORT_DESC,
			$input
		);

		return ApiResponse::success(['data' => $data]);
	}

	/**
	 * add sync request of jp customers to queue
	 * POST - /quickbook/sync_request/{id}/jp_to_qb/sync
	 *
	 * @param  Integer 	| $id 	| Id of Sync Customer
	 * @return response
	 */
	public function queueJPSyncRequest($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getJpOrQbCustomerSyncRules());

		if($validator->fails()){
			return ApiResponse::validation($validator);
		}
			$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		try{
			$token = ($batch->connection_type == QuickbookSyncBatch::QBD);

			if(!$token){
				$token = QuickBooks::getToken();
			}
			if($token){
				$this->qbSyncManagerService->getSyncQueueCustomer($id, QuickbookSyncCustomer::ORIGIN_JP, arry_fu($input['customer_ids']));
				$this->qbSyncManagerService->queueSyncRequest($id, SyncRequestAction::JP_TO_QB, arry_fu($input['customer_ids']));

				return ApiResponse::success([
					'message' => trans('response.success.sync_request_queued'),
				]);
			}

		}  catch(UnauthorizedException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		}  catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

	}

	/**
	 * add sync request of qb customers to queue
	 * POST - /quickbook/sync_request/{id}/qb_to_jp/sync
	 *
	 * @param  Integer 	| $id 	| Id of Sync Customer
	 * @return response
	 */
	public function queueQBSyncRequest($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getJpOrQbCustomerSyncRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		try{
			$token = ($batch->connection_type == QuickbookSyncBatch::QBD);

			if(!$token){
				$token = QuickBooks::getToken();
			}
			if($token){
				$this->qbSyncManagerService->getSyncQueueCustomer($id, QuickbookSyncCustomer::ORIGIN_QB, arry_fu($input['customer_ids']));

				$customers = $this->qbSyncManagerService->queueSyncRequest($id, SyncRequestAction::QB_TO_JP, arry_fu($input['customer_ids']));

				return ApiResponse::success([
					'message' => trans('response.success.sync_request_queued')
				]);
			}
		} catch(UnauthorizedException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * add matching customer sync request in syncing queue
	 * POST - /quickbook/sync_request/{id}/matching_customers/sync
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function queueMatchingCustomersRequest($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getMatchingCustomerSyncRules());

		if($validator->fails()){
			return ApiResponse::validation($validator);
		}

		$jpCustomerIds = array_column($input['details'], 'sync_customer_id');
		$qbCustomerIds = array_column($input['details'], 'sync_qb_id');
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		try{
			$token = ($batch->connection_type == QuickbookSyncBatch::QBD);

			if(!$token){
				$token = QuickBooks::getToken();
			}

			if($token){
				$this->qbSyncManagerService->getSyncQueueCustomer($id, QuickbookSyncCustomer::MATCHING_CUSTOMERS, arry_fu($jpCustomerIds), arry_fu($qbCustomerIds));

				$action = $this->qbSyncManagerService->queueSyncRequest($id, SyncRequestAction::MATCHING_CUSTOMERS, arry_fu($jpCustomerIds), arry_fu($qbCustomerIds));

				return ApiResponse::success([
					'message' => trans('response.success.sync_request_queued'),
				]);
			}
		} catch(UnauthorizedException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * add action required customer sync request in syncing queue
	 * POST - /quickbook/sync_request/{id}/action_required/sync
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function queueActionRequiredCustomersRequest($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getMatchingCustomerSyncRules());

		if($validator->fails()){
			return ApiResponse::validation($validator);
		}

		$jpCustomerIds = array_column($input['details'], 'sync_customer_id');
		$qbCustomerIds = array_column($input['details'], 'sync_qb_id');
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		try{
			$token = ($batch->connection_type == QuickbookSyncBatch::QBD);

			if(!$token){
				$token = QuickBooks::getToken();
			}

			if($token){
				$this->qbSyncManagerService->getSyncQueueCustomer($id, QuickbookSyncCustomer::ACTION_REQUIRED, arry_fu($jpCustomerIds), arry_fu($qbCustomerIds));

				$action = $this->qbSyncManagerService->queueSyncRequest($id, SyncRequestAction::ACTION_REQUIRED, arry_fu($jpCustomerIds), arry_fu($qbCustomerIds));

				return ApiResponse::success([
					'message' => trans('response.success.sync_request_queued'),
				]);
			}
		} catch(UnauthorizedException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * mark a matching customer as different
	 * @param  ID $id [description]
	 * @return [type]     [description]
	 */
	public function matchingCustomersMarkDifferent($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::markCustomersAsDifferentRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		try {
			$response = $this->qbSyncManagerService->markMatchingCustomersDiff($batch->id, $input['details']);

			return ApiResponse::success([
				"message" => "Customer marked as different successfully.",
			]);
		} catch (InvalidSyncCustomerException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * mark a matching customer as same
	 * @param  ID $id [description]
	 * @return [type]     [description]
	 */
	public function matchingCustomersMarkSame($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::markCustomersAsDifferentRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		try {
			$response = $this->qbSyncManagerService->markMatchingCustomersDiffOrSame($batch->id, $input['details'], false);

			return ApiResponse::success([
				"message" => "Customer marked as same successfully.",
			]);
		} catch (InvalidSyncCustomerException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * select a JP customer financial
	 * @param  ID $id [description]
	 * @return [type]     [description]
	 */
	public function selectMatchingCustomerFinancial($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getSelectFinancialRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		try {
			$response = $this->qbSyncManagerService->SelectMatchingCustomerFinancial($batch->id, $input['details'], $input['type']);

			return ApiResponse::success([
				"message" => "Customer financials select successfully.",
			]);
		} catch (InvalidSyncCustomerException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * select a QB customer financial
	 * @param  ID $id [description]
	 * @return [type]     [description]
	 */
	public function selectQbCustomerFinancial($id)
	{
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookSyncCustomer::getSelectFinancialRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		try {
			$response = $this->qbSyncManagerService->SelectMatchingCustomerFinancial($batch->id, $input['details'], false);

			return ApiResponse::success([
				"message" => "QB Customer financials select successfully.",
			]);
		} catch (InvalidSyncCustomerException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	public function saveMappedJobs($id){
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookMappedJob::getRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['customer_id'])
			->where('qb_id', $input['qb_customer_id'])
			->where('action_required', false)
			->firstOrFail();

		$qbCustomer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jpCustomer = Customer::where('id', $syncCustomer->customer_id)
			->where('company_id', getScopeId())
			->firstOrFail();

		try {
			$response = $this->qbSyncManagerService->saveMappedJobs($batch->id, $input['details'], $input['customer_id'], $input['qb_customer_id'], false);

			$syncCustomer->mapped = true;
			$syncCustomer->save();

			return ApiResponse::success([
				"message" => "Customer's jobs mapped successfully.",
			]);
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

	}

	public function saveActionRequiredJobs($id){
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);

		$input = Request::all();
		$validator = Validator::make($input, QuickbookMappedJob::getRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		$syncCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->where('customer_id', $input['customer_id'])
			->where('qb_id', $input['qb_customer_id'])
			->where('action_required', true)
			->firstOrFail();

		$qbCustomer = QBOCustomer::where('qb_id', $syncCustomer->qb_id)
			->where('company_id', getScopeId())
			->whereNull('qb_parent_id')
			->firstOrFail();

		$jpCustomer = Customer::where('id', $syncCustomer->customer_id)
			->where('company_id', getScopeId())
			->firstOrFail();

		try {
			$response = $this->qbSyncManagerService->saveMappedJobs($batch->id, $input['details'], $input['customer_id'], $input['qb_customer_id'], true);

			$syncCustomer->mapped = true;
			$syncCustomer->save();

			return ApiResponse::success([
				"message" => "Customer's jobs mapped successfully.",
			]);
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

	}

	public function getFinancialsOfQBCustomer()
	{
		$input = Request::all();
		$validator = Validator::make($input, QBOCustomer::getQBFinancialRules());

		if($validator->fails()){

			return ApiResponse::validation($validator);
		}

		$qbCustomer = QBOCustomer::where('company_id', getScopeId())
			->where('qb_id', $input['qb_customer_id'])
			->firstOrFail();

		try {
			// $token = QuickBooks::getToken();
			// if($token){
				$financials = $this->qbSyncManagerService->getQBCustomerFinancial($qbCustomer);
				return ApiResponse::success(['data' => $financials]);
			// }

		}  catch(UnauthorizedException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		}  catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * add matching customer sync request in syncing queue
	 * POST - /quickbook/sync_request/{id}/matching_customers
	 *
	 * @param  Integer | $id | Id of a Sync Request
	 * @return response
	 */
	public function saveMatchingCustomers($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, ['sync_customer_id' => 'required', 'sync_qb_id' => 'required']);

		if($validator->fails()){
			return ApiResponse::validation($validator);
		}
		$jpCustomerId =$input['sync_customer_id'];
		$qbCustomerId =$input['sync_qb_id'];
		$batch = $this->qbSyncManagerService->getQBSyncBatchById($id);
		$qbCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->whereNull('customer_id')
			->where('qb_id', $qbCustomerId)
			->where('sync_status', QuickbookSyncCustomer::READY_FOR_SYNCHING)
			->where('action_required', false)
			->firstOrFail();

		$jpCustomer = QuickbookSyncCustomer::where('company_id', getScopeId())
			->where('batch_id', $batch->id)
			->whereNull('qb_id')
			->where('customer_id', $jpCustomerId)
			->where('sync_status', QuickbookSyncCustomer::READY_FOR_SYNCHING)
			->where('action_required', false)
			->firstOrFail();
		try{
			$token = ($batch->connection_type == QuickbookSyncBatch::QBD);

			if(!$token){
				$token = QuickBooks::getToken();
			}

			if($token){
				$action = $this->qbSyncManagerService->saveMatchingCustomers($batch->id, $jpCustomer, $qbCustomer);

				return ApiResponse::success([
					'message' => "Customers mapped successfully.",
				]);
			}
		} catch(UnauthorizedException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalidSyncCustomerException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}