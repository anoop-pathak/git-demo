<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\NotFoundException;
use App\Models\ApiResponse;
use App\Services\Google\GoogleGmailService;
use App\Transformers\GmailThreadTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class GoogleGmailController extends ApiController
{

    protected $googleService;
    protected $response;

    public function __construct(Larasponse $response, GoogleGmailService $googleService)
    {
        $this->response = $response;
        $this->googleService = $googleService;
    }

    /**
     * get thread list
     * @return $data
     */
    public function getThreadList()
    {
        try {
            $input = Request::onlyLegacy('label', 'next_page_token', 'subject');
            $threads = $this->googleService->getThreadList($input);
            $data = $this->response->collection($threads['data'], new GmailThreadTransformer);

            $data['meta']['next_page_token'] = $threads['next_page_token'];

            return ApiResponse::success($data);
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get single thread
     * @param  $threadId
     * @return $data
     */
    public function getSingleThread($threadId)
    {
        try {
            $thread = $this->googleService->getSingleThread($threadId);

            return ApiResponse::success($this->response->collection($thread, new GmailThreadTransformer));
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (NotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get attachment
     * @return $response
     */
    public function getAttachment()
    {
        try {
            $input = Request::onlyLegacy('message_id', 'attachment_id', 'mime_type', 'file_name');

            $validator = Validator::make($input, [
                'message_id' => 'required',
                'attachment_id' => 'required',
                'mime_type' => 'required',
                'file_name' => 'required',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            $attachment = $this->googleService->getAttachment($input['message_id'], $input['attachment_id']);
            $attachment = $attachment->getData();
            $attachment = base64_decode(str_pad(strtr($attachment, '-_', '+/'), strlen($attachment) % 4));

            $response = response($attachment, 200);
            $response->header('Content-Type', $input['mime_type']);
            $response->header('Content-Disposition', 'attachment; filename="' . $input['file_name'] . '"');

            return $response;
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (NotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    // /**
    //  * trash thread
    //  * @param  $threadId
    //  * @return [type]
    //  */
    // public function trashThread($threadId)
    // {
    // 	$input 		= Request::onlyLegacy('is_trash');
    // 	$trash		= $input['is_trash'];
    // 	$message 	= trans('response.success.email_move_to_trash');
    // 	$thread 	= $this->googleService->trashThread($threadId, $trash);

    // 	if (!$trash) {
    //      $message = trans('response.success.email_move_to_inbox');
    // 	}

    // 	return ApiResponse::success([
    // 		'message' => $message,
    // 	]);
    // }

    // /**
    //  * mark as read
    //  * @param  $threadId
    //  * @return $thread
    //  */
    // public function markAsRead($threadId)
    // {
    // 	$input 	= Request::onlyLegacy('is_read');
    // 	$read	= $input['is_read'];

    // 	$thread = $this->googleService->markAsRead($threadId, $read);
    // 	$message = trans('response.success.mark_unread');

    // 	if ($read) {
    // 		$message = trans('response.success.mark_read');
    // 	}

    // 	return ApiResponse::success([
    // 		'message' => $message,
    // 	]);
    // }
}
