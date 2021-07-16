<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Payment;

use Exception;
use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use Illuminate\Support\Facades\Log;
use App\Models\QuickBookTask;
use App\Models\JobPayment;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class ApplyHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entity_id)
    {
        return  JobPayment::find($entity_id);
    }

    function synch($task, $jobPayment)
    {
        $job = $jobPayment->job;
        $customer = $job->customer;
        $paymentInvoices = $jobPayment->invoices()
            ->whereNull('quickbook_invoice_id')
            ->get();

        $invoiceIds = $paymentInvoices->pluck('id')->toArray();

        if($this->isPendingInvoicesTasks($invoiceIds)){
            return $this->reSubmit();
        }

        if(!$this->checkDependentSuccessInvoicesTasks($invoiceIds)){
            if($this->queueJob->attempts() < QuickBookTask::QUEUE_ATTEMPTS){
                throw new Exception("Dependent Invoices Task failed exception.");
            }

            $task->markFailed("It's all Dependent Invoices task is not synched on quickbooks so due to this task is also mark as failed.", $this->queueJob->attempts());
            return $this->queueJob->delete();
        }

        QBPayment::paymentsSync($jobPayment->id, $customer->quickbook_id);

        $jobCredit = JobPayment::find($jobPayment->id);
        return $jobCredit;
    }


    protected function getSuccessLogMessage(){
		$format = "Payment for amount %s  has been successfully applied on QBO";
        $amount = ($this->entity->payment - $this->entity->unapplied_amount);
		$message = sprintf($format, $amount);
		Log::info($message);
		return $message;
	}

    protected function checkPreConditions($jobPayment){

        // Stop Payment if Job not synced
        $job = $jobPayment->job;
        if(!$job->quickbook_id) {
            $this->task->markFailed("Dependency Error: Job not found on Quickbook.", $this->queueJob->attempts());
            $this->resynchCustomerAccount($job->customer_id, QuickBookTask::SYSTEM_EVENT);
            return false;
        }

        return true;
    }

      private function isPendingInvoicesTasks($invoiceIds)
    {
        if(empty($invoiceIds)){
            return false;
        }
        // dependent invoices are in the mid of syncing process
        $pendingInvoiceTaskCount = QuickBookTask::where('company_id', getScopeId())
            ->whereIn('object_id', $invoiceIds)
            ->where('object', QuickBookTask::INVOICE)
            ->whereIn('action', [QuickBookTask::CREATE, QuickBookTask::UPDATE])
            ->whereIn('status', [QuickBookTask::STATUS_PENDING, QuickBookTask::STATUS_INPROGRESS])
            ->count();

        if($pendingInvoiceTaskCount) {
            return true;
        }
        return false;
    }

    private function checkDependentSuccessInvoicesTasks($invoiceIds)
    {
        if(empty($invoiceIds)){
            return true;
        }

        $successInvoiceTaskCount = QuickBookTask::where('company_id', getScopeId())
            ->whereIn('object_id', $invoiceIds)
            ->where('object', QuickBookTask::INVOICE)
            ->where('action', QuickBookTask::CREATE)
            ->where('status', QuickBookTask::STATUS_SUCCESS)
            ->count();

        if($successInvoiceTaskCount != count($invoiceIds)) {
            return false;
        }

        return true;
    }
}