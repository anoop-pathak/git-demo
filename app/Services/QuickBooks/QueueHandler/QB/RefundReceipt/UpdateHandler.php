<?php
namespace App\Services\QuickBooks\QueueHandler\QB\RefundReceipt;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Refund as QBRefund;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Log;

class UpdateHandler extends QBBaseTaskHandler
{
    public function __construct() {}

	function getQboEntity($entityId)
    {
        $response = [];
        $response['entity'] = QBRefund::get($entityId);
        return  $response;
    }

    function synch($task, $refund)
    {
        $jpRefund = QBRefund::actionImport($refund['entity']);

        return $jpRefund;

    }

    protected function checkPreConditions($refund){

        $refund = $refund['entity'];

        $jpRefund = QBRefund::getJpEntity($refund->Id);

        if(!$jpRefund){
            $task = QBRefund::createTask($refund->Id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
            $this->task->markFailed("Refund is not synced in JobProgress so sync it first.", $this->queueJob->attempts());
            return false;
        }

        $customerId = $refund->CustomerRef;
        $accountId = $refund->DepositToAccountRef;

        $jpJob = QBRefund::getJpJob($customerId);
        if(!$jpJob){
            $jpCustomer = QBRefund::getJpCustomer($customerId);
            $object = QuickBookTask::CUSTOMER;

            if($jpCustomer){
                $object = QuickBookTask::GHOST_JOB;
            }

            $task = QBOQueue::addTask($object . ' ' . QuickBookTask::CREATE, [
                    'id' => $customerId,
                ], [
                    'object_id' => $customerId,
                    'object' => $object,
                    'action' => QuickBookTask::CREATE,
                    'origin' => QuickBookTask::ORIGIN_QB,
                    'created_source' => $this->task->createdSource
                ]);

            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }

        //check vendor is synch in JP
        $jpAccount = QBAccount::getJpEntity($accountId);
        if(!$jpAccount){
            $task = QBAccount::createTask($accountId, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
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