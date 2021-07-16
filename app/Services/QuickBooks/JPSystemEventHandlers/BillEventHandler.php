<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class BillEventHandler{
use CustomerAccountHandlerTrait;

    public function subscribe($event) {
		$event->listen('JobProgress.Events.VendorBillCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\BillEventHandler@createBill');
		$event->listen('JobProgress.Events.VendorBillUpdated', 'App\Services\QuickBooks\JPSystemEventHandlers\BillEventHandler@updateBill');
		$event->listen('JobProgress.Events.VendorBillDeleted', 'App\Services\QuickBooks\JPSystemEventHandlers\BillEventHandler@deleteBill');
	}

	public function createBill($event)
	{

		$vendorBill = $event->vendorBill;

		if(!$vendorBill->getQBOId()){
			$this->resynchCustomerAccount($vendorBill->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
    }

    public function updateBill($event)
	{

		$vendorBill = $event->vendorBill;

		if(!$vendorBill->getQBOId()){
			$this->resynchCustomerAccount($vendorBill->customer_id, QuickBookTask::SYSTEM_EVENT);
		} elseif($vendorBill->getQBOId()){
			$meta = [
				'id' => $vendorBill->id,
				'customer_id' => $vendorBill->customer_id,
				'company_id' => $vendorBill->company_id,
			];
			$this->createTask($vendorBill->id, QuickBookTask::BILL, QuickBookTask::UPDATE, $meta);
		}
	}

	public function deleteBill($event)
	{
		$vendorBill = $event->vendorBill;

		if($vendorBill->getQBOId()){
			$meta = [
				'id' => $vendorBill->id,
				'customer_id' => $vendorBill->customer_id,
				'company_id' => $vendorBill->company_id,
			];
			$this->createTask($vendorBill->id, QuickBookTask::BILL, QuickBookTask::DELETE, $meta);
		}
	}

	private function createTask($objectId, $object, $action, $meta)
	{
		QBOQueue::addTask($object . ' ' . $action, $meta, [
			'object_id' => $objectId,
			'object' => $object,
			'action' => $action,
			'origin' => QuickBookTask::ORIGIN_JP,
			'created_source' => QuickBookTask::SYSTEM_EVENT
		]);
	}
}
