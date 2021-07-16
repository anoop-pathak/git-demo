<?php
namespace App\Services\QuickBooks\QueueHandler\QB\RefundReceipt;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Refund as QBRefund;


class DeleteHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  ['entity'=>['Id' => $entityId]];
    }

    function synch($task, $refund)
    {
        $refund = $refund['entity'];

        $jpRefund = QBRefund::getJpEntity($refund['Id']);
        QBRefund::actionDeleteJpEntity($jpRefund);

        return $jpRefund;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}