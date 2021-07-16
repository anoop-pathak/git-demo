<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Payment;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\QuickBooks;


class DeleteHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  ['entity'=>['Id' => $entityId]];
    }

    function synch($task, $payment)
    {
        $payment = $payment['entity'];
        $jpPayment = QuickBooks::getJobPaymentByQBId($payment['Id']);

        QBPayment::delete($payment['Id']);

        return $jpPayment;
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}