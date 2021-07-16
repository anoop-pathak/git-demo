<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Invoice;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\JobInvoice;

class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entity_id)
    {
        return  JobInvoice::find($entity_id);
    }

    function synch($task, $invoice)
    {
        $job = $invoice->job;
        // Stop Invoice Synch if job not synced
        if(!$job->quickbook_id) {

            $task->markFailed("Dependency Error: Job not synced on Quickbook.", $this->queueJob->attempts());
            return $this->queueJob->delete();
        }
        $invoice = QBInvoice::createOrUpdateInvoice($invoice);

        return $invoice;
    }

    protected function checkPreConditions($invoice){

        // check Job exists on quickbooks
        $job = $invoice->job;
        if($job->quickbook_id) {
            $isExists = QuickBooks::isCustomerExistsOnQuickbooks($job->quickbook_id);

            if(!$isExists){
                QuickBooks::unlinkJPEntities($job);
                $this->task->markFailed("Dependency Error: Job not found on Quickbook.", $this->queueJob->attempts());
                $this->resynchCustomerAccount($job->customer_id, $this->task->created_source);
                return false;
            }

            return $isExists;
        }

        return true;
    }
}