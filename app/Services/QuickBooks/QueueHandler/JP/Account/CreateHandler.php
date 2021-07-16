<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Account;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Models\FinancialAccount;

class CreateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  FinancialAccount::find($entity_id);
    }

    function synch($task, $account)
    {

        QBAccount::actionCreate($account);
        $account = FinancialAccount::find($account->id);
        return $account;
    }

    protected function checkPreConditions($account){

        // check it's parent account is synch on quickbooks
        if(!QBAccount::isParentAccountSynched($account)){

            $parentAccount = $account->parent;
            $task = $this->createParentTask($parentAccount);

            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }


        return true;
    }

    private function createParentTask($account){
        $task = QBOQueue::addTask(QuickBookTask::ACCOUNT . ' ' . QuickBookTask::CREATE, [
                'id' => $account->id,
                'company_id' => $account->company_id,
            ], [
                'object_id' => $account->id,
                'object' => QuickBookTask::ACCOUNT,
                'action' => QuickBookTask::CREATE,
                'origin' => QuickBookTask::ORIGIN_JP,
                'created_source' => QuickBookTask::SYSTEM_EVENT
            ]);

        return $task;
    }
}