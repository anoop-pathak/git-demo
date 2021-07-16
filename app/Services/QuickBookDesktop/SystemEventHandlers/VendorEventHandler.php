<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;

class VendorEventHandler
{

	public function subscribe($event)
	{
		$event->listen('JobProgress.Events.VendorCreated', 'App\Services\QuickBookDesktop\SystemEventHandlers\VendorEventHandler@createVendor');
		$event->listen('JobProgress.Events.VendorUpdated', 'App\Services\QuickBookDesktop\SystemEventHandlers\VendorEventHandler@updateVendor');
		$event->listen('JobProgress.Events.VendorDeleted', 'App\Services\QuickBookDesktop\SystemEventHandlers\VendorEventHandler@deleteVendor');
	}

	public function createVendor($event)
	{
		$vendor = $event->vendor;

		if(!$vendor->qb_desktop_id) {

			$username = QBDesktopQueue::getUsername($vendor->company_id);

			if (!$username) {
				return false;
			}

			TaskScheduler::addJpVendorTask(QuickBookDesktopTask::CREATE, $vendor->id, null, $username);
		}
	}

	public function updateVendor($event)
	{
		$vendor = $event->vendor;

		$username = QBDesktopQueue::getUsername($vendor->company_id);

		if (!$username) {
			return false;
		}

		if (!$vendor->qb_desktop_id) {
			TaskScheduler::addJpVendorTask(QuickBookDesktopTask::CREATE, $vendor->id, null, $username);
		} else {
			TaskScheduler::addJpVendorTask(QuickBookDesktopTask::UPDATE, $vendor->id, null, $username);
		}
	}


	public function deleteVendor($event)
	{
		$vendor = $event->vendor;

		if ($vendor->qb_desktop_id) {

			$username = QBDesktopQueue::getUsername($vendor->company_id);

			if (!$username) {
				return false;
			}

			TaskScheduler::addJpVendorTask(QuickBookDesktopTask::DELETE, $vendor->id, null, $username);
		}
	}
}
