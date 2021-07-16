<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;

class BillEventHandler
{

	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

    public function subscribe($event) {
		$event->listen('JobProgress.Events.VendorBillCreated', 'App\Services\QuickBookDesktop\SystemEventHandlers\BillEventHandler@createBill');
		$event->listen('JobProgress.Events.VendorBillUpdated', 'App\Services\QuickBookDesktop\SystemEventHandlers\BillEventHandler@updateBill');
		$event->listen('JobProgress.Events.VendorBillDeleted', 'App\Services\QuickBookDesktop\SystemEventHandlers\BillEventHandler@deleteBill');
	}

	public function createBill($event)
	{

		try {
			$vendorBill = $event->vendorBill;
			$username = QBDesktopQueue::getUsername($vendorBill->company_id);

			if (!$username) {
				return false;
			}

			if (!$vendorBill->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($vendorBill->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpBillTask(QuickBookDesktopTask::CREATE, $vendorBill->id, null, $username);
			}

		} catch (Exception $e) {
		}
    }

    public function updateBill($event)
	{
		try {

			$vendorBill = $event->vendorBill;

			$username = QBDesktopQueue::getUsername($vendorBill->company_id);

			if (!$username) {
				return false;
			}

			if (!$vendorBill->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($vendorBill->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpBillTask(QuickBookDesktopTask::CREATE, $vendorBill->id, null, $username);
			}

		} catch (Exception $e) {
		}
	}

	public function deleteBill($event)
	{
		try {

			$vendorBill = $event->vendorBill;

			$username = QBDesktopQueue::getUsername($vendorBill->company_id);

			if (!$username) {
				return false;
			}

			if ($vendorBill->qb_desktop_txn_id) {
				TaskScheduler::addJpBillTask(QuickBookDesktopTask::DELETE, $vendorBill->id, null, $username);
			}

		} catch (Exception $e) {
		}
	}
}
