<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Job;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Models\Job;

class UpdateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  Job::find($entity_id);
    }

    function synch($task, $job)
    {
        $job = QBCustomer::updateJobInQuickbooks($job);
        return $job;
    }

}