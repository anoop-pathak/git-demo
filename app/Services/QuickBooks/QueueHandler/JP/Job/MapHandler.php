<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Job;

use App\Models\QuickbookMappedJob;
use App\Models\Job;
use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;

class MapHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  Job::find($entity_id);
    }

    function synch($task, $job)
    {
        $meta = $task->payload;
        $qbCustomerId = ine($meta, 'qb_customer_id') ? $meta['qb_customer_id'] : null;

        $mappedJob = QuickbookMappedJob::where('company_id', $job->company_id)
            ->where('job_id', $job->id)
            ->where('qb_customer_id', $qbCustomerId)
            ->where('customer_id', $job->customer_id)
            ->whereNotNull('qb_job_id')
            ->first();
        if(!$mappedJob){
            $task->markFailed("Not Found Error: Mapped Job not Found.", $this->queueJob->attempts());

            return $this->queueJob->delete();
        }

        $response = QBCustomer::get($mappedJob->qb_job_id);

        if(!ine($response, 'entity')) {
            $task->markFailed("Not Found Error: Quickbook Job not Found.", $this->queueJob->attempts());

            return $this->queueJob->delete();
        }

        $job = QBCustomer::mapJobInQuickBooks($job, $response['entity']);

        return $job;
    }

    protected function checkPreConditions($job){

        // Stop Job if customer not synced
        $customer = $job->customer;

        if(!$customer->quickbook_id) {
            $this->task->markFailed("Dependency Error: Customer not synced on Quickbook.", $this->queueJob->attempts());;
            return false;
        }

        return true;
    }
}