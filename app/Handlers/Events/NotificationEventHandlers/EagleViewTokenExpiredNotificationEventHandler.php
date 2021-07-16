<?php 
namespace App\Handlers\Event\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use App\Models\Company;
use App\Models\EVClient;

class EagleViewTokenExpiredNotificationEventHandler
{
	protected $repo;
 	function __construct(NotificationsRepository $repo)
	{
		$this->repo = $repo;
	}
 	public function handle($event) 
	{
		$company = Company::find(getScopeId());
 		if(!$company) return false;
		$user = $company->subscriber;
 		if(!$user) return false;
 		$evClient = EVClient::where('company_id', $company->id)->first();
		if(!$evClient) return false;
 		$msg = trans('response.events.notifications.eagle_view_account_reconnect');
		$emailTemplate = 'emails.reconnect_eagleview_account';
 		$this->repo->notification(null, $user->id, $msg, $user);
 		$data = [
			'first_name'	=> $user->first_name,
			'email'			=> $user->email,
			'ev_account'	=> $evClient->username,
		];
		
		\Mail::send($emailTemplate, $data, function($message) use ($data)
	    {
	        $message->to($data['email'])->subject(trans('response.events.email_subjects.eagleview_reconnect'));
	    });
 	    return $evClient->delete();
	}
}  