<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Invoice;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Models\QuickBookTask;
use App\Models\JobInvoice;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class UpdateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entity_id)
    {
        return  JobInvoice::find($entity_id);
    }

    function synch($task, $invoice)
    {
        $invoice = QBInvoice::createOrUpdateInvoice($invoice);
        return $invoice;
    }

    protected function checkPreConditions($invoice){

		// Stop Invoice if Job not synced
        $job = $invoice->job;
        if(!$job->quickbook_id) {
            $this->task->markFailed("Dependency Error: Job not synced on Quickbook.", $this->queueJob->attempts());
            $this->resynchCustomerAccount($job->customer_id, QuickBookTask::SYSTEM_EVENT);
            return false;
        }

        return true;
	}
}