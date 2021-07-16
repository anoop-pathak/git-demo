<?php
namespace App\Services\QuickBooks\QueueHandler\JP\RefundReceipt;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\Refund as QBRefund;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\JobRefund;

class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entityId)
    {
        return  JobRefund::find($entityId);
    }

    function synch($task, $refund)
    {
        QBRefund::actionCreate($refund);
        $refund = JobRefund::find($refund->id);
        return $refund;
    }

    protected function checkPreConditions($refund)
    {

        // check Job exists on quickbooks
        $job = $refund->job;
        $account = $refund->financialAccount;

        if(!$job->quickbook_id) {
            $this->task->markFailed("Dependency Error: Job not synced on Quickbook.", $this->queueJob->attempts());
            return false;
        }

        if($job->quickbook_id) {
            $isExists = QuickBooks::isCustomerExistsOnQuickbooks($job->quickbook_id);

            if(!$isExists){
                QuickBooks::unlinkJPEntities($job);
                $this->task->markFailed("Dependency Error: Job not found on Quickbook.", $this->queueJob->attempts());
                $this->resynchCustomerAccount($job->customer_id, $this->task->created_source);
                return false;
            }
        }

        if($account && !$account->getQBOId()){
            $task = QBAccount::createTask($account->id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_JP);
            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }

        return true;
    }
}