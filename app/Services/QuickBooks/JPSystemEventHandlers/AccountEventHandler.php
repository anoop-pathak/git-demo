<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QBOQueue;

class AccountEventHandler{

    public function subscribe($event) {
		$event->listen('JobProgress.Events.FinancialAccountCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\AccountEventHandler@syncQuickbooksAccount');
		$event->listen('JobProgress.Events.FinancialAccountUpdated', 'App\Services\QuickBooks\JPSystemEventHandlers\AccountEventHandler@updateQuickbooksAccount');
	}

	function __construct()
	{
	}

	public function syncQuickbooksAccount($event)
	{
		$account = $event->financialAccount;

		if(!$account->quickbook_id){
			$meta = [
				'id' => $account->id,
				'company_id' => $account->company_id
			];
			$this->createTask($account->id, QuickBookTask::ACCOUNT, QuickBookTask::CREATE, $meta);
		}
	}

	public function updateQuickbooksAccount($event)
	{
		$account = $event->financialAccount;
		$meta = [
			'id' => $account->id,
			'company_id' => $account->company_id
		];

		if($account->quickbook_id){
			$this->createTask($account->id, QuickBookTask::ACCOUNT, QuickBookTask::UPDATE, $meta);
		}else{
			$this->createTask($account->id, QuickBookTask::ACCOUNT, QuickBookTask::CREATE, $meta);
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
