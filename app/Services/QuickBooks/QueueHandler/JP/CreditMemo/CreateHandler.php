<?php
namespace App\Services\QuickBooks\QueueHandler\JP\CreditMemo;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\JobCredit;

class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entity_id)
    {
        return  JobCredit::find($entity_id);
    }

    function synch($task, $jobCredit)
    {
        $job = $jobCredit->job;
        // Stop Invoice Synch if job not synced
        if(!$job->quickbook_id) {

            $task->markFailed("Dependency Error: Job not synced on Quickbook.", $this->queueJob->attempts());
            return $this->queueJob->delete();
        }

        $jobCredit = QBCreditMemo::createCreditNote($jobCredit, $jobCredit->note);

        return $jobCredit;
    }

    protected function checkPreConditions($credit){

        // check Job exists on quickbooks
        $job = $credit->job;
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


    protected function getSuccessLogMessage(){
		$format = "Credit for amount %s  has been successfully created in QBO";
		$message = sprintf($format, $this->entity->amount);
		Log::info($message);
		return $message;
	}
}