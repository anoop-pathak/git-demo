<?php
namespace App\Handlers\Events;

use Firebase;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\PhoneMessage;
use App\Models\PhoneCall;
use App\Models\Message;

class MessagesQueueHandler
{
	/**
	* update mark as read count on firebase
	*/
	public function markAsRead($jobQueue, $data = [])
	{
		$scope = setAuthAndScope($data['user_id']);
 		if(!$scope) return $jobQueue->delete();
		Firebase::updateUserMessageCount($data['user_id']);
		Firebase::updateUserTextMessageCount($data['user_id']);
        $jobQueue->delete();
	}
 	public function updateFirebaseMessageCount($jobQueue, $data = [])
	{
		$scope = setAuthAndScope($data['current_user_id']);
		if(!$scope || !ine($data, 'participant_ids')) return $jobQueue->delete();
 		try {
			foreach ($data['participant_ids'] as $user) {
				Firebase::updateUserMessageCount($user);
				Firebase::updateUserTextMessageCount($user);
			}
        	$jobQueue->delete();
		} catch(Exception $e) {
			Log::error($e);
		}
	}

	public function setPhoneMessageStatus($messageQueue, $data = [])
	{
		if(!ine($data, 'MessageSid') || !ine($data, 'MessageStatus')) {
			return $messageQueue->delete();
		}

		try{
			$status = Message::getSMSStatus($data['MessageStatus']);
			Message::where('sms_id', $data['MessageSid'])->update(['sms_status'=> $status]);

			$messageQueue->delete();
		} catch(Exception $e) {
			Log::error($e);
		}
	}

	public function setPhoneCallStatus($callQueue, $data = [])
	{
		if(!ine($data, 'CallSid') || !ine($data, 'CallStatus')) {
			return $callQueue->delete();
		}

		try{
			$callData = [
				'status'	=> $data['CallStatus'],
				'duration'	=> $data['CallDuration']
			];

			PhoneCall::where('sid', $data['CallSid'])->update($callData);
			$callQueue->delete();
		} catch(Exception $e) {
			Log::error($e);
		}
	}
}
