<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

class TwilioEventHandler
{
	public function subscribe($event)
	{
		$event->listen('JobProgress.Twilio.Events.ReleaseTwilioNumberForAllUsers', 'App\Handlers\Events\TwilioEventHandler@releaseTwilioNumberForAllUsers');
		$event->listen('JobProgress.Twilio.Events.ReleaseTwilioNumberForSingleUser', 'App\Handlers\Events\TwilioEventHandler@releaseTwilioNumberForSingleUser');
	}

	/**
	 * Release Twilio Number for all Company Users
	 * @param $event
	 */
	public function releaseTwilioNumberForAllUsers($event)
	{
		$company = $event->company;
		$users = $company->allUsers;
		$loginUserId = Auth::id();
		$companyID = $company->id;
		foreach ($users as $user) {
			Queue::push('\App\Handlers\Events\TwilioQueueHandler@releaseTwilioNumbers', ['user_id' => $user->id, 'login_user_id' => $loginUserId, 'company_id' => $companyID]);
		}
	}

	/**
	 * Release Twilio Number for Single User
	 * @param $event
	 * @return
	 */
	public function releaseTwilioNumberForSingleUser($event)
	{
		$userId = $event->user->id;
		$loginUserId = Auth::id();
		$companyID = $event->user->company_id;
		Queue::push('\App\Handlers\Events\TwilioQueueHandler@releaseTwilioNumbers', ['user_id' => $userId, 'login_user_id' => $loginUserId, 'company_id' => $companyID]);
	}
}
