<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Department;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Models\Division;
use App\Services\QuickBooks\Facades\Department as QBDepartment;

class CreateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return Division::find($entity_id);
    }

    function synch($task, $division)
    {
       QBDepartment::createOrUpdateDivision($division);

       return $job;
    }
}