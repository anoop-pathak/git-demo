<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class PaymentEventHandler
{
	use CustomerAccountHandlerTrait;

	public function subscribe($event) {
		$event->listen('JobProgress.FinancialDetails.Events.PaymentCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\PaymentEventHandler@syncCustomerAccountOnQuickbooks');
	}

	function __construct()
	{
	}

	public function syncCustomerAccountOnQuickbooks($event)
	{
		$payment = $event->payment;
		if(!$payment->quickbook_id){
			$this->resynchCustomerAccount($payment->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
	}
}
