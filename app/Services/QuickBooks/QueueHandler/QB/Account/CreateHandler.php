<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Account;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;

class CreateHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        $response = [];
        $response['entity'] = QBAccount::get($entityId);
        return  $response;
    }

    function synch($task, $account)
    {
        $account = QBAccount::actionImport($account['entity']);

        return $account;
    }

    protected function checkPreConditions($account){

        $account = $account['entity'];

        // check it's parent account is synch in JP
        if($account->ParentRef){
            $jpAccount = QBAccount::getJpEntity($account->ParentRef);
            if(!$jpAccount){
                $parentAccount = QBAccount::get($account->ParentRef);
                $task = QBAccount::createTask($parentAccount->Id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
                $this->task->parent_id = $task ? $task->id : null;
                $this->task->status = QuickBookTask::STATUS_PENDING;
                $this->task->save();
                return false;
            }
        }
        return true;
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}