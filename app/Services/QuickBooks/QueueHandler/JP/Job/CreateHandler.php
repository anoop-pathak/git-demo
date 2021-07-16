<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Job;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Models\Job;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;

class CreateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return Job::find($entity_id);
    }

    function synch($task, $job)
    {
        // Stop Job if Customer not synced
        if(!QBCustomer::isCustomerAccountSynced($job->customer_id, false)) {

            $task->markFailed("Dependency Error: Customer not synced on Quickbook.", $this->queueJob->attempts());
            return $this->queueJob->delete();
        }

        if($job->isProject()){
            $parentJob = $job->parentJob;

            // Stop Project if Parent Job not synced on QB
            if(!$parentJob->quickbook_id) {
                $task->markFailed("Dependency Error: Parent Job not synced on Quickbook.", $this->queueJob->attempts());
                return $this->queueJob->delete();
            }
        }

        QBCustomer::getJobQuickbookId($job, $syncCustomer = false);

        $job = Job::find($job->id);

        return $job;
    }
}