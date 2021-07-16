<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use App\Models\CompanyCamClient;

class CompanyCamSubscriptionExpiredNotificationEventHandler 
{
 	protected $repo;
 	function __construct(NotificationsRepository $repo) {
		$this->repo = $repo;
	}
 	public function handle($event) 
	{
		$client =  CompanyCamClient::where('company_id', $event->companyId)->first();
		$company = $client->company;
		$user = $company->subscriber;
		$msg = trans('response.events.notifications.company_account_reconnect', [
			'attribute' => 'CompanyCam'
		]);
 		
 		$this->repo->notification(null, $user->id, $msg, $user);
 		
 		$data = [
			'first_name' 	 => $user->first_name,
			'email'      	 => $user->email,
		];
 		
 		\Mail::send("emails.company_cam_subscription_expired", $data, function($message) use ($data)
	    {
			$message->to($data['email'])
				->subject(trans('response.events.email_subjects.account_reconnect', [
				'attribute' => 'CompanyCam'
			]));
	    });
 	    $client->delete();
	}
}  