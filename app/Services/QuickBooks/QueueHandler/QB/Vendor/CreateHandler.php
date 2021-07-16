<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Vendor;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use Illuminate\Support\Facades\Log;

class CreateHandler extends QBBaseTaskHandler
{
    public function __construct() {}

	function getQboEntity($entityId)
    {
        $response = [];
        $response['entity'] = QBVendor::get($entityId);
        return  $response;
    }

    function synch($task, $vendor)
    {
        $vendor = QBVendor::actionImport($vendor['entity']);

        return $vendor;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}