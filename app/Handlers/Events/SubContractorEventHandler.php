<?php

namespace App\Handlers\Events;

use App\Services\Subscriptions\SubscriptionServices;
use App\Services\Zendesk\ZendeskService;
use User;
use Exception;
use Log;

class SubContractorEventHandler
{
 	protected $subscriptionServices;
	protected $zendeskService;
 	public function __construct( SubscriptionServices $subscriptionServices, ZendeskService $zendeskService)
	{
 		$this->subscriptionServices = $subscriptionServices;
		$this->zendeskService 		= $zendeskService;
	}
 	public function subscribe($event)
	{
		$event->listen('JobProgress.SubContractors.Events.SubContractorGroupChanged', 'App\Handlers\Events\SubContractorEventHandler@checkCompanySubscription');
		$event->listen('JobProgress.SubContractors.Events.SubContractorGroupChanged', 'App\Handlers\Events\SubContractorEventHandler@createSupportAccount');
 		$event->listen('JobProgress.SubContractors.Events.SubContractorActivateOrDeactivate', 'App\Handlers\Events\SubContractorEventHandler@checkCompanySubscription');
		$event->listen('JobProgress.SubContractors.Events.SubContractorUpdated', 'App\Handlers\Events\SubContractorEventHandler@updateSupportAccount');
		$event->listen('JobProgress.SubContractors.Events.SubContractorDeleted', 'JobProgress\EventHandlers\SubContractorEventHandler@checkCompanySubscription');
	}
 	public function checkCompanySubscription( $event )
	{
		$subContractor = $event->subContractor;
 		$this->subscriptionServices->checkForNextUpdation( $subContractor->company );
	}
 	public function createSupportAccount( $event )
	{
		try {
			$subContractor = $event->subContractor;
			$company = $subContractor->company;
 			if($company->zendesk_id && (!$subContractor->zendesk_id) && ($subContractor->group_id == User::GROUP_SUB_CONTRACTOR_PRIME)) {
				$zendeskUser = $this->zendeskService->addUser($subContractor,$company->zendesk_id);
				$subContractor->zendesk_id = $zendeskUser ? $zendeskUser->id : null;
				$subContractor->save();
			}
		}catch (Exception $e) {
			// throw $e;
			Log::error($e);
		}
	}
 	public function updateSupportAccount( $event )
	{
		try {
			$subContractor = $event->subContractor;
			$company = $subContractor->company;
 			if($company->zendesk_id && $subContractor->zendesk_id && $subContractor->group_id == User::GROUP_SUB_CONTRACTOR_PRIME) {
				$zendeskUser = $this->zendeskService->updateUser($subContractor);
				$subContractor->zendesk_id = $zendeskUser->id;
				$subContractor->save();
			}
		}catch (Exception $e) {
			// throw $e;
			Log::error($e);
		}
	}
} 