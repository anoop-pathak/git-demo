<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Account;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Models\QuickBookTask;
use App\Models\FinancialAccount;

class UpdateHandler extends BaseTaskHandler
{
    protected $ensureCreationBeforeUpdate = false;

	function getEntity($entity_id)
    {
        return  FinancialAccount::find($entity_id);
    }

    function synch($task, $account)
    {
        QBAccount::actionUpdate($account);
        $account = FinancialAccount::find($account->id);

        return $account;
    }

    protected function checkPreConditions($account){

        // if account is not synched on quickbooks
        if(!$account->quickbook_id){
            $task = QBAccount::createTask($account->id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_JP);
            $this->task->markFailed("Account not synced on Quickbooks so sync it first.", $this->queueJob->attempts());
            return false;
        }


        return true;
    }
}