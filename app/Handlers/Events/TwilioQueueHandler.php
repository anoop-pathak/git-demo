<?php
namespace App\Handlers\Events;

use Log;
use Exception;
use App\Models\TwilioNumber;
use App\Services\Twilio\TwilioNumberService;

class TwilioQueueHandler
{
	public function __construct(TwilioNumberService $twilioNumberService)
	{
		$this->twilioNumberService = $twilioNumberService;
	}

	/**
	 * Release Twilio Number
	 * @param  $twilioQueue
	 * @param  $data
	 * @return Response
	 */
	public function releaseTwilioNumbers($twilioQueue, $data = null)
	{
		$userId = ine($data, 'user_id') ? $data['user_id'] : null;
		$loginUserId = ine($data, 'login_user_id') ? $data['login_user_id'] : null;

		try{
			$twilioNumber = TwilioNumber::where('company_id', $data['company_id'])
										->where('user_id', $userId)
										->first();
			if (!$twilioNumber) {
				return $twilioQueue->delete();
			}

			$this->twilioNumberService->releaseTwilioNumber($twilioNumber);

			// set Deleted By
			$twilioNumber->update(['deleted_by' => $loginUserId]);
			$twilioNumber->delete();

			// Delete Queue after completing
			$twilioQueue->delete();
			return true;
		} catch(Exception $e) {
			Log::error($e);
			if($twilioQueue->attempts() > config('queue.failed_attempts')) {
				$twilioQueue->delete();
			}
			return true;
		}
	}
}