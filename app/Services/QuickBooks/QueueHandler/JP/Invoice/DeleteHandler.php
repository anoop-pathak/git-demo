<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Invoice;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Models\JobInvoice;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  JobInvoice::withTrashed()->findOrFail($entity_id);
    }

    function synch($task, $invoice)
    {
        QBInvoice::deleteJobInvoice($invoice);
        return $invoice;
    }
}