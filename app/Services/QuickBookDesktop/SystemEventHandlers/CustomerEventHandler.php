<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use App\Models\Customer;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use QBDesktopQueue;

class CustomerEventHandler
{
	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

	public function subscribe($event)
	{
		$event->listen(
			'JobProgress.Customers.Events.CustomerSynched',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CustomerEventHandler@syncOnQBD'
		);

		$event->listen(
			'JobProgress.Customers.Events.CustomerCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CustomerEventHandler@createOnQBD'
		);

		$event->listen(
			'JobProgress.Customers.Events.CustomerUpdated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CustomerEventHandler@updateOnQBD'
		);
	}

	public function syncOnQBD($event)
	{
		try {

			$customer = $event->customer;

			if (!$customer->qb_desktop_id) {
				$this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			}

		} catch (Exception $e) {
		}
	}

	public function createOnQBD($event)
	{
		try {

			$customerId = $event->customerId;
			$customer = Customer::find($customerId);

			if (!$customer->qb_desktop_id) {
				$this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			}

		} catch (Exception $e) {
		}
	}

	public function updateOnQBD($event)
	{
		try {
			$customerId = $event->customerId;
			$customer = Customer::find($customerId);

			$username = QBDesktopQueue::getUsername($customer->company_id);

			if (!$username) {
				return false;
			}

			if (!$customer->qb_desktop_id) {
				$this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} elseif ($customer->qb_desktop_id) {
				TaskScheduler::addJpCustomerTask(QuickBookDesktopTask::UPDATE, $customer->id, null, $username);
			}

		} catch (Exception $e) {
		}
	}
}
