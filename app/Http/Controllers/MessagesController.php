<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Message;
use App\Services\Messages\MessageService;
use App\Transformers\MessagesTransformer;
use App\Transformers\MessageThreadTransformer;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\DB;
use App\Exceptions\UsersRequiredException;
use App\Repositories\MessageRepository;

class MessagesController extends ApiController
{

    protected $response;
    protected $repo;

    public function __construct(Larasponse $response, MessageService $service, MessageRepository $repo)
    {
        $this->response = $response;
        $this->service = $service;
        $this->repo = $repo;
        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
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

        if(ine($input, 'all_job_messages')
            && ine($input, 'job_id')
            && (!Auth::user()->isSubContractorPrime()))
        {
            unset($input['current_user_id']);
            unset($input['unread_thread']);
        }

        if (ine($input, 'customer_all_user_messages')) {
			unset($input['current_user_id']);
		}

        $singleUserThread = $this->service->getSingleUserThread($input);

        if ($singleUserThread) {
            $singleUserThread = $this->response->item($singleUserThread, new MessageThreadTransformer);
        }

        $threadLists = $this->service->getThreadList($input);
        if (!$limit) {
            $threadLists = $threadLists->get();
            $data = $this->response->collection($threadLists, new MessageThreadTransformer);
            $data['single_user_thread'] = $singleUserThread;

            return ApiResponse::success($data);
        }
        $threadLists = $threadLists->paginate($limit);

        $data = $this->response->paginatedCollection($threadLists, new MessageThreadTransformer);
        $data['single_user_thread'] = $singleUserThread;

        return ApiResponse::success($data);
    }

    /**
     * Get messages by thread id
     * @param  int $threadId thread id
     * @return messages
     */
    public function getThreadMessages($threadId)
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $threadId = $this->service->getThreadById($threadId);
        try {
            $threadLists = $this->service->getThreadMessages($threadId, $input);

            if (!$limit) {
                $threadLists = $threadLists->get();

                return ApiResponse::success($this->response->collection($threadLists, new MessagesTransformer));
            }
            $threadLists = $threadLists->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($threadLists, new MessagesTransformer));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.message_not_send'), $e);
        }
    }

    /**
     * Send message
     * Post /messages/send
     * @return response
     */
    public function send_message()
    {
        $input = Request::onlyLegacy('participants', 'subject', 'content', 'thread_id', 'job_id', 'send_as_email', 'customer_id', 'tag_ids', 'participant_setting');

        $validator = Validator::make($input, Message::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tags = arry_fu((array)$input['tag_ids']);
		if(!empty($tags) && !\App\Helpers\SecurityCheck::hasPermission('message_to_user_groups')) {

			return ApiResponse::errorForbidden('Access Forbidden.');
		}

        if(!empty($input['tag_ids']) && $input['thread_id']) {

			return ApiResponse::errorGeneral('You cannot send both Tag id(s) and Thread id at once');
        }

        if(empty($input['job_id']) && $input['participant_setting']) {

			return ApiResponse::errorGeneral('Participant Settings will not work without job');
		}

		if(!empty($tags)) {
			$usersId = DB::table('user_tag')->whereIn('tag_id', $tags)->pluck('user_id')->toArray();
			if(empty($usersId)) {

				return ApiResponse::errorGeneral('Tag has not any participants');
			}
			$input['participants'] = array_merge((array)$input['participants'], $usersId);
        }

        if (empty($input['participants']) && !ine($input, 'thread_id')) {

			return ApiResponse::errorGeneral('Participants cannot be empty.');
		}

		DB::beginTransaction();

        try {
            $senderId = Auth::id();
            $message = $this->service->sendMessage(
                $senderId,
                $input['participants'],
                $input['subject'],
                $input['content'],
                $input
            );
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.sent', ['attribute' => 'Message']),
                'data' => $this->response->item($message, new MessagesTransformer)
            ]);
        } catch(UsersRequiredException $e){
			DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.message_not_send'), $e);
        }
    }

    /**
     * Unread message count
     * Get /messages/unread_count
     * @return count
     */
    public function unreadMessagesCount()
    {
        $input = Request::onlyLegacy('last_read_message_id', 'thread_id');

        $userId = \Auth::id();
        $count = $this->service->getUnreadMessagesCount($userId, $input);

        return ApiResponse::success(['count' => $count]);
    }

    /**
     * Unread message
     * Put messages/{thread_id}/mark_as_unread
     * @return response
     */
    public function threadMarkAsUnread($threadId) {
        $userId = \Auth::id();
        $message = $this->service->threadMarkAsUnread($userId, $threadId);
        return ApiResponse::success([
            'message' => trans('response.success.marked_as_unread',['attribute' => 'Thread']),
        ]);
    }

    public function recentActivity()
	{
		$input = Request::all();
		$limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');
		$message = $this->repo->getRecentActivity($input);
		$message = $message->paginate($limit);
		$response =  $this->response->paginatedCollection($message, new MessagesTransformer);

		return ApiResponse::success($response);

	}
}
