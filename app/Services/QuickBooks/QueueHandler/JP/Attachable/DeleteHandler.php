<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Attachable;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Attachable as QBAttachable;
use App\Models\Attachable;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  Attachable::find($entity_id);
    }

    function synch($task, $attachable)
    {
        QBAttachable::actionDelete($attachable);
        return $attachable;
    }
}