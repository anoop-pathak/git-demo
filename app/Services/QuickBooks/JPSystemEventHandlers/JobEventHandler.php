<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;


class JobEventHandler{
	use CustomerAccountHandlerTrait;

	public function subscribe($event) {
		$event->listen('JobProgress.Jobs.Events.JobCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\JobEventHandler@syncCustomerAccountOnQuickbooks');
	}

	function __construct()
	{
	}

	public function syncCustomerAccountOnQuickbooks($event)
	{
		$job = $event->job;
		if(!$job->quickbook_id){
			$this->resynchCustomerAccount($job->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
	}
}
