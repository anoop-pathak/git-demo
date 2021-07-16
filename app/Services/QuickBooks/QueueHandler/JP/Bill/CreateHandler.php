<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Bill;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use App\Services\QuickBooks\Facades\Account as QBAccount;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\VendorBill;
use App\Models\FinancialAccount;

class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entityId)
    {
        return  VendorBill::find($entityId);
    }

    function synch($task, $bill)
    {
        QBBill::actionCreate($bill);
        $bill = VendorBill::find($bill->id);
        return $bill;
    }

    protected function checkPreConditions($bill)
    {

        // check Job exists on quickbooks
        $job = $bill->job;
        $vendor = $bill->vendor;
        $accountIds = $bill->lines->pluck('financial_account_id')->toArray();
        $accounts = FinancialAccount::where('company_id', $bill->company_id)
            ->whereIn('id', (array)$accountIds)
            ->whereNull('quickbook_id')
            ->get();

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

        if(!$vendor->getQBOId()){
            $task = QBVendor::createTask($vendor->id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_JP);
            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }

        if(!$accounts->isEmpty()){
            $parentId = null;
            foreach ($accounts as $account) {
                 $task = QBAccount::createTask($account->id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_JP);
                 if($task){
                    $parentId =  $task->id; // last task id save as bill's parent id

                 }
            }
            $this->task->parent_id = $parentId;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;

        }

        return true;
    }
}