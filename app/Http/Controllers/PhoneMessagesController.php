<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\PhoneMessages\PhoneMessagesService;
use App\Exceptions\PhoneMessageException;
use App\Exceptions\TwilioException;
use App\Transformers\PhoneMessagesTransformer;
use App\Exceptions\InvalideAttachment;
use App\Exceptions\FirebaseException;
use Request;
use App\Models\ApiResponse;
use App\Models\PhoneMessage;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Models\Message;
use App\Transformers\MessageThreadTransformer;
use App\Services\PhoneMessages\WebhookResponseParser;
use Illuminate\Support\Facades\Auth;

class PhoneMessagesController extends ApiController
{

 	/**
	 * Display a listing of the resource.
	 * GET /companies
	 *
	 * @return Response
	 */
	protected $response;
	protected $company;
	protected $service;

 	public function __construct(Larasponse $response, PhoneMessagesService $service )
	{
		$this->response = $response;
		$this->service = $service;

 		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}

 	/**
	 * Get message thread lists
	 * Get /messages/thread_list
	 * @return response
	 */
	public function getThreadList()
	{
		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$input['current_user_id'] = Auth::id();
		$threadLists = $this->service->getThreadList($input);

		return $this->getResponse($threadLists, new MessageThreadTransformer($input), $this->response, $limit);
	}

 	/**
	 * Get messages by thread id
	 * @param  int $threadId    thread id
	 * @return messages
	 */
	public function getThreadMessages($threadId)
	{
		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$thread = $this->service->getThreadById($threadId);
		$threadMessages = $this->service->getThreadMessages($thread, $input);

		return $this->getResponse($threadMessages, new PhoneMessagesTransformer, $this->response, $limit);
	}

 	/**
	 * Send Messages
	 * @return response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, Message::getSMSRules());

 		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		$companyId = getScopeId();
		$company = Company::findOrFail($companyId);
		try {
			$input['sender_id'] = Auth::id();
	        $message = $this->service->send($company, $input['phone_number'], $input['message'], $input);
			DB::commit();
	        return ApiResponse::success([
	        	'message'  => trans('response.success.message_sent'),
	        	'data'	   => $this->response->item($message, new PhoneMessagesTransformer),
	    	]);
		} catch(TwilioException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PhoneMessageException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalideAttachment $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(FirebaseException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Get Reply from Twilio and Save in DB
	 *
	 * @return Response
	 */
	public function savePhoneMessageReply()
	{
		$input = Request::all();
		//get twilio sms status response
		Log::info('Twilio Reply: Save phone message reply'.print_r($input,1));
		try {
			$parser = new WebhookResponseParser($input);
			$this->service->saveReplyFromTwilio($parser->get());

			return ApiResponse::success([
	        	'message'  => trans('response.success.message_received'),
	    	]);

		} catch(TwilioException $e) {
			Log::error($e->getMessage());
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(PhoneMessageException $e) {
			Log::error($e->getMessage());
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			Log::error($e->getMessage());
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}


 	public function getMessageNotifications()
	{
		//get twilio sms status response
		Log::info('Twilio Notification'.print_r(Request::all(),1));
		$input = Request::onlyLegacy('MessageSid', 'MessageStatus');

		Queue::push('App\Handlers\Events\MessagesQueueHandler@setPhoneMessageStatus', $input);

		return ApiResponse::success([
				'message' => 'success',
			]);
	}
}