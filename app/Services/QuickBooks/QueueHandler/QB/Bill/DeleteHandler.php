<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Bill;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Models\JobFinancialCalculation;

class DeleteHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  ['entity'=>['Id' => $entityId]];
    }

    function synch($task, $bill)
    {
        $bill = $bill['entity'];

        $jpBill = QBBill::getJpEntity($bill['Id']);

        $jpBill->lines()->delete();
        $jpBill->delete();
        JobFinancialCalculation::updateJobFinancialbillAmount($jpBill->job);

        return $jpBill;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }

     protected function checkPreConditions($bill)
     {
        $entity = $bill['entity'];

        $jpBill = QBBill::getJpEntity($entity['Id']);
        if(!$jpBill){
            $this->task->markFailed("Object not found on JP.", $this->queueJob->attempts());
            return false;
        }

        return true;

     }
}