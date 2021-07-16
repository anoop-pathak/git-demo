<?php

namespace App\Http\Controllers;

use Request;
use App\Models\ApiResponse;
use App\Helpers\SecurityCheck;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use App\Services\Hover\HoverService;
use App\Transformers\HoverTransformer;
use App\Repositories\HoverJobRepository;
use App\Exceptions\UnauthorizedException;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\RequestException;
use App\Exceptions\AccountNotConnectedException;
use App\Transformers\HoverJobListingTransformer;
use App\Transformers\HoverUserTransformer;
use App\Exceptions\Hover\HoverUserNotExistException;
use App\Exceptions\AccountAlreadyConnectedException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Transformers\HoverCaptureRequestTransformer;
use Exception;
use App\Models\HoverCaptureRequest;

class HoverController extends ApiController
{
	protected $service;
	protected $jobRepo;
	protected $response;
 	public function __construct(HoverService $service,  JobRepository $jobRepo,
		Larasponse $response, HoverJobRepository $repo)
	{
		$this->service = $service;
		$this->jobRepo = $jobRepo;
		$this->response = $response;
		$this->repo = $repo;
 		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
 		parent::__construct();
	}
 	/**
	 * Connect sandbox account
	 *
	 * @return redirect to response url
	 */
	public function connect()
	{
		try {
 			$url = $this->service->authentication();
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
 		return \redirect($url);
	}
 	/**
	* get response
	*
	* @return hover client detail i.e access token
	*/
	public function response()
	{
		DB::beginTransaction();
		try {
			$inputs = Request::onlyLegacy('company_id', 'user_id', 'code', 'hover_auth');

			//redirect to login page if connected from hover
			if(!ine($inputs, 'company_id') && !ine($inputs, 'user_id')){
				$data = [
					'hover_code'   => $inputs['code'],
					'redirect_url' => config('hover.redirect_url')
				];
				$url = config('jp.login_url').'?'.http_build_query($data);

				return Redirect::to($url);
			}

			$this->service->saveTokenAndWebhook($inputs['code'], $inputs['user_id'], $inputs['company_id'], $inputs['hover_auth']);
 			DB::commit();
 			return view('google_redirect');
 		} catch(AccountAlreadyConnectedException $e){
			DB::rollback();
 			return view('account_already_connected');
 		} catch (\Exception $e) {
			DB::rollback();
 			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
 	/**
	* get notification after webhook creation
	*
	*/
	public function notification()
	{
		$input = Request::onlyLegacy('code', 'event', 'job_id', 'state','webhook_id');
 		Log::info($input);

		try {
 			switch ($input['event']) {
				case config('hover.webhook_verification_event'):
					$this->service->verifyWebhook($input['code'], $input['webhook_id']);
					break;
				case config('hover.webhook_job_state_change_event'):
					$this->service->jobStateManage($input['job_id'],$input['state'], $input['webhook_id']);
					break;
				case config('hover.webhook_deliverable-change-request-state-changed'):
					$this->service->jobStateManage($input['job_id'],$input['state'], $input['webhook_id']);
					break;
			}
		} catch(UnauthorizedException $e){
			//token expire
		} catch (\Exception $e) {
			Log::error($e);
		}
	}
 	/**
	* disconnect account and delete client
	*/
	public function disconnect()
	{
		try {
			if(!SecurityCheck::AccessOwner(Auth::user())) return SecurityCheck::$error;
 			$this->service->disconnect();
 			return ApiResponse::success([
				'message' => trans('response.success.disconnected', ['attribute' => 'Account'])
			]);
		} catch (\Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
 	/**
	* get job listing
	*
	*/
	public function jobListing()
	{
		$input = Request::all();

		$jobs = $this->repo->getHoverJobs($input);
		$jobsQueryBuilder = $this->jobRepo->getJobsQueryBuilder($input, ['customers'])
			->whereNull('customers.deleted_at')
			->whereNull('jobs.deleted_at')
			->select('customers.first_name as first_name', 'customers.email as email', 'customers.last_name as last_name', 'jobs.number', 'jobs.customer_id', 'jobs.parent_id', 'jobs.id', 'jobs.alt_id');
		$jobsJoinQuery = generateQueryWithBindings($jobsQueryBuilder);
		$jobs->join(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.id', '=', 'hover_jobs.job_id');


		if(Auth::user()->isSubContractorPrime()) {
			$jobs->whereIn('hover_jobs.job_id', function($query) {
				$query->select('job_sub_contractor.job_id')
					->from('job_sub_contractor')
					->where('job_sub_contractor.sub_contractor_id', Auth::id());
			});
		}

		//$jobs->select('customers.first_name', 'customers.email', 'customers.last_name', 'jobs.number', 'jobs.customer_id', 'hover_jobs.*', 'jobs.parent_id');
		$jobs->select('first_name', 'email', 'last_name', 'jobs.number', 'jobs.customer_id', 'hover_jobs.*', 'jobs.parent_id', 'jobs.alt_id');

		$jobs->with('hoverImage', 'pdfReport');
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
			$jobs = $jobs->get();

			return ApiResponse::success(
				$this->response->collection($jobs, new HoverJobListingTransformer)
			);
		}

		$jobs = $jobs->paginate($limit);

		return ApiResponse::success(
			$this->response->paginatedCollection($jobs, new HoverJobListingTransformer)
		);
	}
 	/**
	* sync jobs to hover
	*
	*/
	public function syncHoverJob()
	{
		DB::beginTransaction();
		try {
			$input = Request::all();
			$validator = Validator::make($input, [
				'job_id' => 'required'
			]);
  			if( $validator->fails() ){

   				return ApiResponse::validation($validator);
    		}
			$job = $this->jobRepo->getById($input['job_id']);

			if(!ine($input, 'hover_user_id')) {
				if(!$job->hover_user_id) {

 					return ApiResponse::errorGeneral('The hover user id field is required.');
				}
				$input['hover_user_id'] = $job->hover_user_id;
			}

			$hoverJob = $this->service->fullJobSync($job, $input);
			DB::commit();
 			return ApiResponse::success([
				'message' => 'Job syned to hover successfully.',
				'data'    => $this->response->item($hoverJob, new HoverTransformer)
			]);
 		} catch(AccountNotConnectedException $e){
			DB::rollback();
 			return ApiResponse::errorGeneral($e->getMessage());
		} catch(HoverUserNotExistException $e){
			DB::rollback();
 			return ApiResponse::errorGeneral($e->getMessage());
 		} catch(UnauthorizedException $e){
			DB::rollback();
 			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e){
			DB::rollback();
 			return ApiResponse::errorNotFound(trans('response.error.not_found',['attribute' => 'Job']));
		} catch (\Exception $e) {
			DB::rollback();
 			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function userListing()
	{
		try {
			$input = Request::all();
			$users = $this->service->getUsers($input);

			return $this->transform($users);
		} catch(AccountNotConnectedException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(HoverUserNotExistException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * create capture request for job
	 */
	public function createCaptureRequest()
	{
		$input = Request::all();
		$validator = Validator::make($input, [
			'customer_name'	 =>	'required',
			'customer_email' =>	'required',
			'deliverable_id' =>	'required',
			'hover_user_id'  =>	'required',
			'hover_user_email' => 'required',
			'job_address' => 'required',
			'job_id' => 'required',
		]);
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->getById($input['job_id']);
		if($job->sync_on_hover) {
			return ApiResponse::errorGeneral('The capture request is already existed.');
		}

		DB::beginTransaction();
		try {

			$hoverJob = $this->service->captureRequestWithSynced($job,  $input);
			DB::commit();
			return ApiResponse::success([
				'message' => 'Capture Request Created successfully.',
				'data'	  => $this->response->item($hoverJob, new HoverJobListingTransformer)
			]);
		} catch(AccountNotConnectedException $e){
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(HoverUserNotExistException $e){
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e){
			DB::rollback();
			return ApiResponse::errorNotFound(trans('response.error.not_found',['attribute' => 'Capture Request']));
		} catch (Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Delete Capture Request
	 */
	public function deleteCaptureRequest($catureRequestId)
	{
		try {
			$captureRequest = HoverCaptureRequest::find($catureRequestId);
			if(!$captureRequest) {
				throw new ModelNotFoundException(trans('response.error.not_found',['attribute' => 'Hover Capture Request']));
			}
			$response = $this->service->deleteCaptureRequest($captureRequest->capture_request_id, $captureRequest->current_user_email);
			if($response) {
				$captureRequest->delete();
			}
			return ApiResponse::success([
				'message' => 'Capture Request Deleted successfully.',
			]);
		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound($e->getMessage());
		} catch (Exception $e) {

		}
	}

	/**
	 * Get Hover Images Url
	 */
	public function getImageUrls($id)
	{
		try {
			$imgUrls = $this->service->getHoverImageUrls($id);

			return ApiResponse::success(['data' => $imgUrls]);
		} catch(AccountNotConnectedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	/**
	 * Get Hover Images Url
	 */
	public function getJobDetail($id)
	{
		try {
			$jobDetail = $this->service->getJobDetail($id);

			return ApiResponse::success(['data' => $jobDetail]);
		} catch(AccountNotConnectedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}

	}

	/**
	 * Save/Hover Job photo
	 * POST /hover/save_photo
	 *
	 * @return Response
	 */
	public function savePhoto()
	{
		$input = Request::onlyLegacy('image_id', 'save_to');

		$validator = Validator::make($input, ['image_id' => 'required', 'save_to' => 'required']);

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		try {
			$this->service->savePhoto($input['image_id'], $input['save_to']);

			return ApiResponse::success(['message' => 'Photo saved successfully.']);
		} catch(AccountNotConnectedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	/**
	* Change Deliverable Id
	* POST /hover/change_deliverable
	*
	* @return Response
	*/
	public function changeDeliverable()
	{
		$input = Request::onlyLegacy('job_id', 'new_deliverable_id');
		$validator = Validator::make($input, ['job_id' => 'required', 'new_deliverable_id' => 'required']);
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		try {
			$data = $this->service->changeDeliverableId($input['job_id'], $input['new_deliverable_id']);

			return ApiResponse::success([
				'message' => 'Hover Deliverable changed successfully.'
			]);
		} catch(AccountNotConnectedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound(trans('response.error.not_found',['attribute' => 'Job']));
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	/**
	 * Get Organization Detail
	 *
	 * @return organization detail
	 */
	public function getOrganizationDetail()
	{
		try {
			$orgs = $this->service->getOrgsDetail();

			return ApiResponse::success(['data' => $orgs]);
		} catch(AccountNotConnectedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	private function transform($users)
	{
		$data = $users['results'];
 		$data = $this->response->collection($data, new HoverUserTransformer);
		$data['meta']['pagination'] = $users['pagination'];
 		return ApiResponse::success($data);
	}
}