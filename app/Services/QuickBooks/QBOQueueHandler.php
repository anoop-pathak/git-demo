<?php
namespace App\Services\QuickBooks;

use Exception;
use QuickBooks;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\QuickbookWebhookEntry;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobCredit;
use App\Models\JobPayment;

class QBOQueueHandler
{
    public function addTask($name, $payload, $meta = [])
    {
        try {
            $taskMeta = [
                'name' => $name,
                'payload' => $payload,
                'status' => QuickBookTask::STATUS_PENDING,
                'created_by' => Auth::user()->id,
                'company_id' => getScopeId(),
            ];

            if(ine($meta, 'object')) {
                $taskMeta['object'] = $meta['object'];
            }

            if(ine($meta, 'object_id')) {
                $taskMeta['object_id'] = $meta['object_id'];
            }

            if(ine($meta, 'action')) {
                $taskMeta['action'] = $meta['action'];
            }

            if(ine($meta, 'parent_id')) {

                $taskMeta['parent_id'] = $meta['parent_id'];
            }

            if(ine($meta, 'group_id')) {

                $taskMeta['group_id'] = $meta['group_id'];
            }

            if(ine($meta, 'origin')) {
                $taskMeta['origin'] = $meta['origin'];
            } else {
                $taskMeta['origin'] = 0; // JobProgress
            }

            if(ine($meta, 'exra')) {
                $taskMeta['exra'] = $meta['exra'];
            }


            if(ine($meta, 'created_source')) {
                $taskMeta['created_source'] = $meta['created_source'];
            }

            if(ine($meta, 'quickbook_webhook_id')) {
                $taskMeta['quickbook_webhook_id'] = $meta['quickbook_webhook_id'];
            }

            if(ine($meta, 'quickbook_webhook_entry_id')) {
                $taskMeta['quickbook_webhook_entry_id'] = $meta['quickbook_webhook_entry_id'];
            }

            if(ine($meta, 'object_last_updated')) {
                $taskMeta['object_last_updated'] = $meta['object_last_updated'];
            }

            $action = ine($meta, 'action') ? $meta['action'] : QuickBookTask::CREATE;
            $task = QuickBookTask::where('origin', $taskMeta['origin'])
                ->where('company_id', getScopeId())
                ->where('action', $action)
                ->whereNotIn('status', [QuickBookTask::STATUS_ERROR, QuickBookTask::STATUS_SUCCESS]);

            if(ine($meta, 'object')) {
                $task->where('object', $meta['object']);
            }

            if(ine($meta, 'object_id')) {
                $task->where('object_id', $meta['object_id']);
            }

            if(ine($meta, 'object_last_updated')) {
                $task->where('object_last_updated', $meta['object_last_updated']);
            }

            $task = $task->first();

            if($task) {

                return $task;
            }
            $task = QuickBookTask::create($taskMeta);

            // Log::info('Add Task:Succes', [$task->id]);

            return $task;

        } catch(Exception $e) {

            Log::error('Add Task:Error', [func_get_args()]);

            throw $e;
        }
    }

    public function getTask($conditions)
    {
        return QuickBookTask::where($conditions)->first();
    }

    public function get($taskId)
    {
        $task = QuickBookTask::whereId($taskId)->first();

        return $task;
    }

    public function markInProgress($taskId, $entryId = null)
    {
        $task = QuickBookTask::whereId($taskId)->first();

        $task->status = QuickBookTask::STATUS_INPROGRESS;

        $task->save();

        $this->updateWebhookEntryStatus($task->quickbook_webhook_entry_id, QuickBookTask::STATUS_INPROGRESS);

        $this->updateSyncStatus($task->object, $task->object_id, $task->status, $task->origin);

        return $task;
    }

    public function markSuccess($taskId)
    {
        $task = QuickBookTask::whereId($taskId)->first();
        $task->status = QuickBookTask::STATUS_SUCCESS;
        $task->save();

        $this->updateWebhookEntryStatus($task->quickbook_webhook_entry_id, QuickBookTask::STATUS_SUCCESS);

        $this->updateSyncStatus($task->object, $task->object_id, $task->status, $task->origin);

        // if($task->group_id){
        //     $this->updateCustomerAccountSyncStatus($task->group_id, $task->company_id);
        // }

        return $task;
    }

    public function markFailed($taskId, $msg = '')
    {
        $task = QuickBookTask::whereId($taskId)->first();

        $task->status = QuickBookTask::STATUS_ERROR;
        $task->msg = $task->msg . ' ' . $msg;
        $task->save();

        $this->updateWebhookEntryStatus($task->quickbook_webhook_entry_id, QuickBookTask::STATUS_ERROR, $msg);

        $this->updateSyncStatus($task->object, $task->object_id, $task->status, $task->origin);

        // if($task->group_id){
        //     QuickbookSyncCustomer::where('company_id', $task->company_id)
        //         ->where('group_id', $task->group_id)
        //         ->update(['sync_status' => QuickbookSyncCustomer::SYNC_FAILED]);
        // }

        return $task;
    }

    public function markPending($task, $msg = '')
    {
        if(!($task instanceof QuickBookTask)) {
            $task = QuickBookTask::whereId((int) $task)->first();
        }

        if(!$task) return false;

        $task->status = QuickBookTask::STATUS_PENDING;

        if($msg) {
            $task->msg = $task->msg . ' ' . $msg;
        }

        $task->save();

        return $task;
    }

    public function isValidTask($meta, $return = false)
    {
        $action = ine($meta, 'action') ? $meta['action'] : QuickBookTask::CREATE;

        $task = QuickBookTask::where('origin', $meta['origin'])
            ->where('company_id', getScopeId())
            ->where('action', $action)
            ->whereNotIn('status', [QuickBookTask::STATUS_ERROR, QuickBookTask::STATUS_SUCCESS]);

        if(ine($meta, 'object')) {

            $task->where('object', $meta['object']);
        }

        if(ine($meta, 'object_id')) {

            $task->where('object_id', $meta['object_id']);
        }

        if(ine($meta, 'object_last_updated')) {

            $task->where('object_last_updated', $meta['object_last_updated']);
        }

        $task = $task->first();

        if($return) {

            return $task;
        }

        if(empty($task)) {

            return true;
        }

        return false;
    }

    public function updateEntityStatus($entity, $status)
    {
        $entity->quickbook_sync_status = $status;
        $entity->save();

        return $entity;
    }

    public function checkParentTaskStatus($task)
    {
        $task->status = QuickBookTask::STATUS_INPROGRESS;

        if($task->parent_id && ($parentTask = QuickBookTask::find($task->parent_id))) {

            switch ($parentTask->status) {
                case QuickBookTask::STATUS_PENDING:
                case QuickBookTask::STATUS_INPROGRESS:
                    $task->status = QuickBookTask::STATUS_PENDING;

                    break;
                case QuickBookTask::STATUS_ERROR:
                    // $task->status = QuickBookTask::STATUS_ERROR;
                    // $task->msg = $task->msg . ' ' . "Parent Queue Error: ".$parentTask->msg;
                    throw new Exception("Parent Queue Error: ". $parentTask->msg);
                    break;
            }
        }
        $task->save();

        return $task;
    }

    /**
	 * Update Entry Status
	 */

	public function updateWebhookEntryStatus($enttryId, $status, $meta = '')
	{

        if(!$enttryId) return false;

        $entry = QuickbookWebhookEntry::whereId($enttryId)->first();

        if(empty($entry)) return false;

        $entry->status = $status;

        if(!empty($msg)) {

            //$entry->meta = $meta;
        }

        return $entry->save();
    }

    /**
	 * Update Entry QuickBook Sync Status
     * @todo rewrite it with polymorphism instead of if else
	 */

	public function updateSyncStatus($entity, $entityId, $status, $origin = null)
	{

        try {

            if (!$entity || !$entityId) {

                Log::info("Update Sync: validation fault", func_get_args());

                return false;
            }

            $syncStatus = null;

            $syncStatus = QuickBooks::getQuickBookSyncStatus($status);

            if ($entity == 'Customer') {

                if ($origin == QuickBookTask::ORIGIN_JP) {

                    $customer = Customer::find($entityId);
                } else if ($origin == QuickBookTask::ORIGIN_QB) {

                    $customer = Customer::where('quickbook_id', $entityId)
                        ->where('company_id', getScopeId())
                        ->first();

                    if (!$customer) {

                        $job = QuickBooks::getJobByQBId($entityId);

                        if ($job && empty($job->ghost_job)) {

                            Job::withTrashed()->where('id', $job->id)
                                ->update(['quickbook_sync_status' => $syncStatus]);

                            $job->quickbook_sync_status = $syncStatus;

                            return $job;
                        }
                    }
                }

                if (empty($customer)) {

                    Log::info("Update Sync: Customer not found");

                    return false;
                }

                Customer::where('id', $customer->id)->update(['quickbook_sync_status' => $syncStatus]);

                $customer->quickbook_sync_status = $syncStatus;

                return $customer;
            }

             if ($entity == 'GhostJob' && ($origin == QuickBookTask::ORIGIN_QB)) {

                $job = null;

                $job = QuickBooks::getJobByQBId($entityId);

                if ($job && $job->ghost_job) {

                    Job::withTrashed()->where('id', $job->id)
                        ->update(['quickbook_sync_status' => $syncStatus]);

                    $job->quickbook_sync_status = $syncStatus;

                    return $job;
                }

                return false;
            }

            if ($entity == 'Job') {

                $job = null;

                $job = Job::find($entityId);

                if (!$job) {

                    return false;
                }

                // we are updating this with model for stoping model events
                Job::where('id', $job->id)->update([
                    'quickbook_sync_status' => $syncStatus
                ]);

                $job->quickbook_sync_status = $syncStatus;

                return $job;
            }

            if ($entity == 'Invoice') {

                if ($origin == QuickBookTask::ORIGIN_JP) {

                    $invoice = JobInvoice::find($entityId);

                } else if ($origin == QuickBookTask::ORIGIN_QB) {

                    $invoice = QuickBooks::getJobInvoiceByQBId($entityId);
                }

                if (!$invoice) {

                    Log::info("Update Sync: Invoice not found");

                    return false;
                }

                $invoice->quickbook_sync_status = $syncStatus;

                return $invoice->save();
            }

            if ($entity == 'CreditMemo') {

                if ($origin == QuickBookTask::ORIGIN_JP) {

                    $jobCredit = JobCredit::find($entityId);
                } else if ($origin == QuickBookTask::ORIGIN_QB) {

                    $jobCredit = QuickBooks::getJobCreditByQBId($entityId);
                }

                if (!$jobCredit) {

                    Log::info("Update Sync: CreditMemo not found");

                    return false;
                }

                $jobCredit->quickbook_sync_status = $syncStatus;

                return $jobCredit->save();
            }

            if ($entity == 'Payment') {

                if ($origin == QuickBookTask::ORIGIN_JP) {

                    $jobPayment = JobPayment::find($entityId);
                } else if ($origin == QuickBookTask::ORIGIN_QB) {

                    $jobPayment = QuickBooks::getJobPaymentByQBId($entityId);
                }

                if (!$jobPayment) {

                    Log::info("Update Sync: Payment not found");

                    return false;
                }

                // we are updating this with model for stoping model events
                JobPayment::where('id', $jobPayment->id)
                    ->update(['quickbook_sync_status' => $syncStatus]);

                $jobPayment->quickbook_sync_status = $syncStatus;

                return $jobPayment;
            }

        } catch (Exception $e) {

            Log::info("Update Sync: Error", [$e->getMessage()]);

            Log::error($e);

            return false;
        }
    }

    /***
     * Update QuickBook Sync Customer Status.
     */
    public function updateCustomerAccountSyncStatus($groupId, $companyId)
    {
        if(!$groupId || !$companyId){
            return false;
        }

        $status = null;

        $totalTaskCount = QuickBookTask::where('company_id', $companyId)
            ->where('group_id', $groupId)
            ->count();

        $successTaskCount = QuickBookTask::where('company_id', $companyId)
            ->where('group_id', $groupId)
            ->where('status', QuickBookTask::STATUS_SUCCESS)
            ->count();

        $failedTaskCount = QuickBookTask::where('company_id', $companyId)
            ->where('group_id', $groupId)
            ->where('status', QuickBookTask::STATUS_ERROR)
            ->count();

            if(!empty($failedTaskCount)){
                $status = QuickbookSyncCustomer::SYNC_FAILED;
            }

            if($totalTaskCount == $successTaskCount){
                $status = QuickbookSyncCustomer::SYNC_COMPLETE;
            }

        if($status){
            QuickbookSyncCustomer::where('company_id', $companyId)
                ->where('group_id', $groupId)
                ->update(['sync_status' => $status]);
        }

        return true;

    }

    /***
     * Get QuickBook task name.
     */
    public function getQuickBookTaskName($meta)
    {
        $name = $meta['object'] . ' ' .$meta['operation'];

        return $name;
    }
}