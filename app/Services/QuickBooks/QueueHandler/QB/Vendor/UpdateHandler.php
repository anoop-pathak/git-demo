<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Vendor;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use Illuminate\Support\Facades\Log;

class UpdateHandler extends QBBaseTaskHandler
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

     protected function checkPreConditions($vendor){

        $vendor = $vendor['entity'];

        // check vendor is synch in JP
        $jpVendor = QBVendor::getJpEntity($vendor->Id);
        if(!$jpVendor){
            $task = QBVendor::createTask($vendor->Id, QuickBookTask::CREATE, $this->task->created_source, QuickBookTask::ORIGIN_QB);
            $this->task->parent_id = $task ? $task->id : null;
            $this->task->status = QuickBookTask::STATUS_PENDING;
            $this->task->save();
            return false;
        }
        return true;
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}