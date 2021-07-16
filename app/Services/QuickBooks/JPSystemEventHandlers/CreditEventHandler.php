<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Services\QuickBooks\Facades\QBOQueue;

class CreditEventHandler{

	use CustomerAccountHandlerTrait;

	public function subscribe($event) {
		$event->listen('JobProgress.Credits.Events.CreditCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\CreditEventHandler@syncCustomerAccountOnQuickbooks');
		$event->listen('JobProgress.Credits.Events.CreditApplied', 'App\Services\QuickBooks\JPSystemEventHandlers\CreditEventHandler@applyCreditOnQuickbooks');
	}

	function __construct()
	{
	}

	public function syncCustomerAccountOnQuickbooks($event)
	{
		$credit = $event->credit;
		if(!$credit->quickbook_id){
			$this->resynchCustomerAccount($credit->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
	}

	public function applyCreditOnQuickbooks($event)
	{
		$credit = $event->credit;

		if($credit->quickbook_id){
			QBOQueue::addTask(QuickBookTask::CREDIT_MEMO . ' ' . QuickBookTask::APPLY, [
					'id' => $credit->id,
					'customer_id' => $credit->customer_id,
					'company_id' => $credit->company_id,
				], [
					'object_id' => $credit->id,
					'object' => QuickBookTask::CREDIT_MEMO,
					'action' => QuickBookTask::APPLY,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
		}
	}
}
