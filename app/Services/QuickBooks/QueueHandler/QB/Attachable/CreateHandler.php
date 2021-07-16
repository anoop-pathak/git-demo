<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Attachable;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Attachable as QBAttachable;
use App\Models\Attachable;
use Illuminate\Support\Facades\Log;

class CreateHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        $response = [];
        $response['entity'] = Attachable::find($entityId);
        return  $response;
    }

    function synch($task, $attachable)
    {
        $attachable = QBAttachable::actionImport($attachable['entity']);

        return $attachable;
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}