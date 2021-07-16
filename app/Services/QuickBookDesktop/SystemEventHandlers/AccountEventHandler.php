<?php
namespace App\Services\QuickBookDesktop\SystemEventHandlers;

use App\Models\QuickBookDesktopTask;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;

class AccountEventHandler
{
	public function subscribe($event)
	{
		$event->listen(
			'JobProgress.Events.FinancialAccountCreated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\AccountEventHandler@syncAccount'
		);

		$event->listen(
			'JobProgress.Events.FinancialAccountUpdated',
			'App\Services\QuickBookDesktop\SystemEventHandlers\AccountEventHandler@updateAccount'
		);

		$event->listen(
			'JobProgress.Events.FinancialAccountDeleted',
			'App\Services\QuickBookDesktop\SystemEventHandlers\AccountEventHandler@deleteAccount'
		);
	}

	public function syncAccount($event)
	{
		$account = $event->financialAccount;

		if(!$account->qb_desktop_id) {

			$username = QBDesktopQueue::getUsername($account->company_id);

			if (!$username) {
				return false;
			}

			TaskScheduler::addJpAccountTask(QuickBookDesktopTask::CREATE, $account->id, null, $username, ['sync_jp_account' => true]);
		}
	}

	public function updateAccount($event)
	{
		$account = $event->financialAccount;

		$username = QBDesktopQueue::getUsername($account->company_id);

		if (!$username) {
			return false;
		}

		if (!$account->qb_desktop_id) {
			TaskScheduler::addJpAccountTask(QuickBookDesktopTask::CREATE, $account->id, null, $username, ['sync_jp_account' => true]);
		} else {
			TaskScheduler::addJpAccountTask(QuickBookDesktopTask::UPDATE, $account->id, null, $username, ['sync_jp_account' => true]);
		}
	}

	public function deleteAccount($event)
	{
		$account = $event->financialAccount;

		if ($account->qb_desktop_id) {

			$username = QBDesktopQueue::getUsername($account->company_id);

			if (!$username) {
				return false;
			}

			TaskScheduler::addJpAccountTask(QuickBookDesktopTask::DELETE, $account->id, null, $username);
		}
	}
}
