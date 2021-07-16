<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;


class PaymentEventHandler{

	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

	public function subscribe($event)
	{
		$event->listen(
			'JobProgress.FinancialDetails.Events.PaymentCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler@create'
		);

		$event->listen(
			'JobProgress.FinancialDetails.Events.CreditPaymentCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler@creditPaymentCreated'
		);

		$event->listen(
			'JobProgress.FinancialDetails.Events.PaymentApplied',
			'App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler@apply'
		);

		$event->listen(
			'JobProgress.FinancialDetails.Events.PaymentUpdated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler@updated'
		);

		$event->listen(
			'JobProgress.FinancialDetails.Events.PaymentCancelled',
			'App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler@cancelled'
		);
	}

	public function creditPaymentCreated($event)
	{

		try {

			$payment = $event->payment;

			$job = $payment->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$payment->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}

	public function create($event)
	{

		try {

			$payment = $event->payment;

			$job = $payment->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$payment->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpPaymentTask(QuickBookDesktopTask::CREATE, $payment->id, null, $username);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}

	public function apply($event)
	{
		try {

			$payment = $event->payment;

			$job = $payment->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$payment->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpPaymentTask(QuickBookDesktopTask::CREATE, $payment->id, null, $username);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}

	}

	public function updated($event)
	{
		try {

			$payment = $event->payment;

			$job = $payment->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$payment->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpPaymentTask(QuickBookDesktopTask::CREATE, $payment->id, null, $username);
			}
		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}

	public function cancelled($event)
	{
		try {

			$payment = $event->payment;

			$job = $payment->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if ($payment->qb_desktop_txn_id) {
				TaskScheduler::addJpPaymentTask(QuickBookDesktopTask::DELETE, $payment->id, null, $username);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}
}
