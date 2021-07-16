<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Vendor;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\QuickBookTask;
use App\Models\Vendor;


class UpdateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

    protected $ensureCreationBeforeUpdate = false;
	function getEntity($entity_id)
    {
        return  Vendor::find($entity_id);
    }

    function synch($task, $vendor)
    {
        QBVendor::actionUpdate($vendor);
        $vendor = Vendor::find($vendor->id);
        return $vendor;
    }

    protected function checkPreConditions($vendor){

        // if vendor is not synched on quickbooks
        if(!$vendor->quickbook_id){
            $task = QBVendor::createTask($vendor->id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_JP);
            $this->task->markFailed("Vendor not synced on Quickbooks so sync it first.", $this->queueJob->attempts());
            return false;
        }
        return true;
    }
}