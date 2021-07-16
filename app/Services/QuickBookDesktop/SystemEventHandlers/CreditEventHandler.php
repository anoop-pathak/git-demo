<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use Illuminate\Support\Facades\Log;

class CreditEventHandler{

	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

	public function subscribe($event) {

		$event->listen(
			'JobProgress.Credits.Events.CreditCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CreditEventHandler@create'
		);

		$event->listen(
			'JobProgress.Credits.Events.CreditApplied',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CreditEventHandler@apply'
		);

		$event->listen(
			'JobProgress.Credits.Events.CreditCancelled',
			'App\Services\QuickBookDesktop\SystemEventHandlers\CreditEventHandler@cancelled'
		);
	}

	public function create($event)
	{
		try {

			$credit = $event->credit;

			$username = QBDesktopQueue::getUsername($credit->company_id);

			if (!$username) {
				return false;
			}

			if (!$credit->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($credit->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}

	public function apply($event)
	{
		try {

			$credit = $event->credit;

			$username = QBDesktopQueue::getUsername($credit->company_id);

			if (!$username) {
				return false;
			}

			if (!$credit->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($credit->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} else {
				TaskScheduler::addJpCreditMemoTask(QuickBookDesktopTask::CREATE, $credit->id, null, $username);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}

	public function cancelled($event)
	{
		try {

			$credit = $event->credit;

			$username = QBDesktopQueue::getUsername($credit->company_id);

			if (!$username) {
				return false;
			}

			if ($credit->qb_desktop_txn_id) {
				TaskScheduler::addJpCreditMemoTask(QuickBookDesktopTask::DELETE, $credit->id, null, $username);
			}

		} catch (Exception $e) {
			Log::warning($e->getMessage());
		}
	}
}
