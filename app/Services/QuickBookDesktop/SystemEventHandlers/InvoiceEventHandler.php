<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;

class InvoiceEventHandler
{
	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

	public function subscribe($event) {
		$event->listen(
			'JobProgress.JobInvoices.Events.InoviceCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\InvoiceEventHandler@create'
		);

		$event->listen(
			'JobProgress.JobInvoices.Events.InoviceUpdated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\InvoiceEventHandler@update'
		);

		$event->listen(
			'JobProgress.JobInvoices.Events.InoviceDeleted',
			'App\Services\QuickBookDesktop\SystemEventHandlers\InvoiceEventHandler@delete'
		);
	}

	public function create($event)
	{
		try {
			$invoice = $event->invoice;
			$username = QBDesktopQueue::getUsername($invoice->job->company_id);

			if (!$username) {
				return false;
			}

			if (!$invoice->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($invoice->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			}

		} catch (Exception $e) {
		}
	}

	public function update($event)
	{
		try {
			$invoice = $event->invoice;
			$username = QBDesktopQueue::getUsername($invoice->job->company_id);

			if (!$username) {
				return false;
			}

			if (!$invoice->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($invoice->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpInvoiceTask(QuickBookDesktopTask::CREATE, $invoice->id, null, $username);
			}

		} catch (Exception $e) {

		}
	}

	public function delete($event)
	{
		try {
			$invoice = $event->invoice;
			$username = QBDesktopQueue::getUsername($invoice->job->company_id);

			if (!$username) {
				return false;
			}

			if ($invoice->qb_desktop_txn_id) {
				TaskScheduler::addJpInvoiceTask(QuickBookDesktopTask::DELETE, $invoice->id, null, $username);
			}

		} catch (Exception $e) {

		}
	}
}
