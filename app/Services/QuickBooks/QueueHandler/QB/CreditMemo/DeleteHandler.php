<?php
namespace App\Services\QuickBooks\QueueHandler\QB\CreditMemo;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\QuickBooks\Facades\QuickBooks;
use Illuminate\Support\Facades\Log;

class DeleteHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  ['entity'=>['Id' => $entityId]];
    }

    function synch($task, $credit)
    {
        $credit = $credit['entity'];

        $jpCredit = QuickBooks::getJobCreditByQBId($credit['Id']);

        QBCreditMemo::delete($credit['Id']);
        return $jpCredit;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}