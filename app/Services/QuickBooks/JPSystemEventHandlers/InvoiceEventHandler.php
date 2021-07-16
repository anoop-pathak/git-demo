<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class InvoiceEventHandler{

	use CustomerAccountHandlerTrait;
	public function subscribe($event) {
		$event->listen('JobProgress.JobInvoices.Events.InoviceCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\InvoiceEventHandler@syncCustomerAccountOnQuickbooks');
	}

	function __construct()
	{
	}

	public function syncCustomerAccountOnQuickbooks($event)
	{
		$invoice = $event->invoice;
		if(!$invoice->getQBOId()){
			$this->resynchCustomerAccount($invoice->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
	}
}
