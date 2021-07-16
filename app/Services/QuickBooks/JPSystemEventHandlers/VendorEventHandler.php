<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Entity\Vendor;
use Exception;
use Illuminate\Support\Facades\App;

class VendorEventHandler
{
    public function subscribe($event) {
		$event->listen('JobProgress.Events.VendorCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\VendorEventHandler@createVendor');
		$event->listen('JobProgress.Events.VendorUpdated', 'App\Services\QuickBooks\JPSystemEventHandlers\VendorEventHandler@updateVendor');
		$event->listen('JobProgress.Events.VendorDeleted', 'App\Services\QuickBooks\JPSystemEventHandlers\VendorEventHandler@deleteVendor');
	}

	public function createVendor($event)
	{

		$vendor = $event->vendor;

		if(!$vendor->quickbook_id){
			$meta = [
				'id' => $vendor->id,
				'company_id' => $vendor->company_id
			];

			$this->createTask($vendor->id, QuickBookTask::VENDOR, QuickBookTask::CREATE, $meta);
		}
	}

	public function updateVendor($event)
	{
		$vendor = $event->vendor;
		$meta = [
			'id' => $vendor->id,
			'company_id' => $vendor->company_id
		];

		if($vendor->quickbook_id){
			$this->createTask($vendor->id, QuickBookTask::VENDOR, QuickBookTask::UPDATE, $meta);
		}else{
			$this->createTask($vendor->id, QuickBookTask::VENDOR, QuickBookTask::CREATE, $meta);
		}
	}


	public function deleteVendor($event)
	{
		try {
			$vendor = $event->vendor;
			$vendorEntity = App::make(Vendor::class);
			$vendorEntity->actionDelete($vendor);
		} catch (Exception $e) {
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
