<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use QBDesktopQueue;
use App\Models\QuickBookDesktopTask;
use Carbon\Carbon;

class JobEventHandler
{
	use CustomerAccountHandlerTrait;

	function __construct()
	{
		$this->settings = app()->make(Settings::class);
	}

	public function subscribe($event)
	{
		$event->listen(
			'JobProgress.Jobs.Events.Saved',
			'App\Services\QuickBookDesktop\SystemEventHandlers\JobEventHandler@syncOnQBD'
		);

		$event->listen(
			'JobProgress.Jobs.Events.JobSynched',
			'App\Services\QuickBookDesktop\SystemEventHandlers\JobEventHandler@syncOnQBD'
		);

		// $event->listen(
		// 	'JobProgress.Jobs.Events.JobUpdated',
		// 	'JobProgress\QuickBookDesktop\SystemEventHandlers\JobEventHandler@updateOnQBD'
		// );
	}

	public function syncOnQBD($event)
	{
		try {

			$job = $event->job;

			$customer = $job->customer;

			$username = QBDesktopQueue::getUsername($customer->company_id);

			if (!$username) {
				return false;
			}

			$delayTime = Carbon::now()->addSeconds(5);

			if (!$job->qb_desktop_id) {
				$this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::SYSTEM_EVENT, $delayTime);
			} elseif ($job->qb_desktop_id) {
				TaskScheduler::addJpJobTask(QuickBookDesktopTask::CREATE, $job->id, null, $username);
			}

		} catch (Exception $e) {

		}
	}

	public function updateOnQBD($event)
	{
		try {

			$job = $event->job;

			// if (!$this->settings->setCompanyScope(null, $job->company_id)) {
			// 	return;
			// }

			$customer = $job->customer;

			$username = QBDesktopQueue::getUsername($customer->company_id);

			if (!$username) {
				return false;
			}

			if (!$job->qb_desktop_id) {
				$this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::SYSTEM_EVENT);
			} elseif ($job->qb_desktop_id) {
				TaskScheduler::addJpJobTask(QuickBookDesktopTask::CREATE, $job->id, null, $username);
			}

		} catch (Exception $e) {
		}
	}
}
