<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Bill;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Models\VendorBill;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  VendorBill::withTrashed()->find($entity_id);
    }

    function synch($task, $bill)
    {
        QBBill::actionDelete($bill);
        return $bill;
    }
}