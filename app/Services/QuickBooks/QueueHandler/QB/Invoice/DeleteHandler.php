<?php
namespace App\Services\QuickBooks\QueueHandler\QB\Invoice;

use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QuickBooks;

class DeleteHandler extends QBBaseTaskHandler
{
	function getQboEntity($entityId)
    {
        return  ['entity'=>['Id' => $entityId]];
    }

    function synch($task, $invoice)
    {
        $invoice = $invoice['entity'];

        $jpInvoice = QuickBooks::getJobInvoiceByQBId($invoice['Id']);

        QBInvoice::delete($invoice['Id']);
        return $jpInvoice;

    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to %s in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}