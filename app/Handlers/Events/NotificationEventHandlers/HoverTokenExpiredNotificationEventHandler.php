<?php 

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use User;
use App\Models\HoverClient;

class HoverTokenExpiredNotificationEventHandler 
{
 	protected $repo;
 	function __construct(NotificationsRepository $repo) {
		$this->repo = $repo;
	}
 	public function handle($event) 
	{
		
		$hoverClient =  HoverClient::where('company_id', $event->companyId)->first();
 		$company = $hoverClient->company;
		$user = $company->subscriber;
		$msg = trans('response.events.notifications.company_hover_account_reconnect');
 		$this->repo->notification(null, $user->id, $msg, $user);
 		$data = [
			'first_name' 	 => $user->first_name,
			'email'      	 => $user->email,
		];
		
		\Mail::send("emails.company_hover_account_reconnect", $data, function($message) use ($data)
	    {
	        $message->to($data['email'])->subject(trans('response.events.email_subjects.hover_reconnect'));
	    });
 	    $hoverClient->delete();
	}
}  