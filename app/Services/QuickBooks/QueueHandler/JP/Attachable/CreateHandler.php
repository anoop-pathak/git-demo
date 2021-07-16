<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Attachable;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Attachable as QBAttachable;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\Attachable;
use App\Models\VendorBill;

class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entityId)
    {
        return Attachable::find($entityId);
    }

    function synch($task, $attachable)
    {
        QBAttachable::actionCreate($attachable);

        $attachable = Attachable::find($attachable->id);
        return $attachable;
    }

    protected function checkPreConditions($attachable)
    {

        $bill = VendorBill::find($attachable->jp_object_id);

        if($bill && !$bill->quickbook_id) {
            $this->task->markFailed("Dependency Error: Job not synced on Quickbook.", $this->queueJob->attempts());
            return false;
        }

        return true;
    }
}