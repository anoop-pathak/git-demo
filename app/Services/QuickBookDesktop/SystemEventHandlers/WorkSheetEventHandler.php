<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use Illuminate\Support\Facades\Log;

class WorkSheetEventHandler
{

	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

    public function subscribe($event) {
		$event->listen(
			'JobProgress.Events.WorkSheetCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\WorkSheetEventHandler@create'
		);
		$event->listen(
			'JobProgress.Events.WorkSheetUpdated', 'App\Services\QuickBookDesktop\SystemEventHandlers\WorkSheetEventHandler@update'
		);
		$event->listen(
			'JobProgress.Events.WorkSheetDeleted', 'App\Services\QuickBookDesktop\SystemEventHandlers\WorkSheetEventHandler@delete'
		);
	}

	public function create($event)
	{

		try {

			$worksheet = $event->worksheet;

			$job = $worksheet->job;

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$worksheet->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
				TaskScheduler::addJpEstimateTask(QuickBookDesktopTask::CREATE, $worksheet->id, null, $username, ['sync_jp_account' => true]);
			}

		} catch (Exception $e) {
			Log::info($e->getMessage());
		}
	}

	public function update($event)
	{
		try {

			$worksheet = $event->worksheet;

			$job = $worksheet->job;

			if (!$this->settings->setCompanyScope(null, $job->company_id)) {
				return;
			}

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if (!$worksheet->qb_desktop_txn_id) {
				$this->resynchCustomerAccount($job->customer->id, QuickBookDesktopTask::SYSTEM_EVENT);

				TaskScheduler::addJpEstimateTask(QuickBookDesktopTask::CREATE, $worksheet->id, null, $username, ['sync_jp_account' => true]);

			} else {

				TaskScheduler::addJpEstimateTask(QuickBookDesktopTask::QUERY, $worksheet->id, null, $username);
			}

		} catch (Exception $e) {
			Log::info($e->getMessage());
		}
	}


	public function delete($event)
	{
		try {

			$worksheet = $event->worksheet;

			$job = $worksheet->job;

			if (!$this->settings->setCompanyScope(null, $job->company_id)) {
				return;
			}

			$username = QBDesktopQueue::getUsername($job->company_id);

			if (!$username) {
				return false;
			}

			if ($worksheet->qb_desktop_txn_id) {
				TaskScheduler::addJpEstimateTask(QuickBookDesktopTask::DELETE, $worksheet->id, null, $username);
			}

		} catch (Exception $e) {
			Log::info($e->getMessage());
		}
	}
}
