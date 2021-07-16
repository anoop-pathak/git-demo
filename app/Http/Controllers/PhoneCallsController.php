<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;
use App\Services\PhoneCalls\PhoneCallsService;
use App\Exceptions\PhoneCallException;
use App\Exceptions\TwilioException;
use App\Transformers\PhoneCallsTransformer;
use Request;
use App\Models\ApiResponse;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class PhoneCallsController  extends ApiController
{

 	/**
	 * Display a listing of the resource.
	 * GET /companies
	 *
	 * @return Response
	 */
	protected $response;
	protected $company;
	protected $scope;
	protected $service;

 	public function __construct( Larasponse $response, Context $scope, PhoneCallsService $service )
	{
		$this->response = $response;
		$this->scope = $scope;
		$this->service = $service;

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
		$input = Request::all();
		$calls = $this->service->getFilteredCalls($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

 		$calls =  $calls->paginate($limit);

 		return ApiResponse::success($this->response->paginatedCollection( $calls, new PhoneCallsTransformer));
	}

 	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$input = Request::all();
		$call = $this->service->getById($id);

 		return ApiResponse::success([
			'data' => $this->response->item($call, new PhoneCallsTransformer)
			]);
	}

 	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['phone_number'=>'required']);

 		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

 		$company = Company::findOrFail(getScopeId());
		DB::beginTransaction();
		try {
			$countryCode = $company->country->code;
	        $call = $this->service->voiceCall($input['phone_number'], $countryCode, $input);

 	        return ApiResponse::success([
				'data' => $this->response->item($call, new PhoneCallsTransformer),
			]);
		} catch(TwilioException $e) {

 			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PhoneCallException $e) {

 			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

 	}

 	public function getVoiceNotifications()
	{
		// get twilio voice status response
		Log::info('Twilio Notification'.print_r(Request::all(),1));
		$input = Request::onlyLegacy('CallSid', 'CallStatus', 'CallDuration');
		Queue::push('App\Handlers\Events\MessagesQueueHandler@setPhoneCallStatus', $input);

		return ApiResponse::success([
				'message' => 'success',
			]);
	}


}