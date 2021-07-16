<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Models\Customer;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Services\QuickBooks\Facades\QBOQueue;

class CustomerEventHandler{
	use CustomerAccountHandlerTrait;

	public function subscribe($event) {
		$event->listen('JobProgress.Customers.Events.CustomerSynched', 'App\Services\QuickBooks\JPSystemEventHandlers\CustomerEventHandler@syncCustomerAccountOnQuickbooks');
		$event->listen('JobProgress.Customers.Events.CustomerUpdated', 'App\Services\QuickBooks\JPSystemEventHandlers\CustomerEventHandler@updateCustomerAccountOnQuickbooks');
	}

	function __construct()
	{
	}

	public function syncCustomerAccountOnQuickbooks($event)
	{
		$customer = $event->customer;
		if(!$customer->quickbook_id){
			$this->resynchCustomerAccount($customer->id, QuickBookTask::SYSTEM_EVENT);
		}
	}

	public function updateCustomerAccountOnQuickbooks($event)
	{
		$customerId = $event->customerId;
		$customer = Customer::find($customerId);
		if(!$customer->quickbook_id){
			$this->resynchCustomerAccount($customer->id, QuickBookTask::SYSTEM_EVENT);
		}elseif($customer->quickbook_id){
			QBOQueue::addTask(QuickBookTask::CUSTOMER . ' ' . QuickBookTask::UPDATE, [
					'id' => $customer->id,
					'customer_id' => $customer->id,
					'company_id' => $customer->company_id,
				], [
					'object_id' => $customer->id,
					'object' => QuickBookTask::CUSTOMER,
					'action' => QuickBookTask::UPDATE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
		}
	}
}
