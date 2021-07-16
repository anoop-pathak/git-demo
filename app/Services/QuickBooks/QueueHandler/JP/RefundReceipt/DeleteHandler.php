<?php
namespace App\Services\QuickBooks\QueueHandler\JP\RefundReceipt;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Refund as QBRefund;
use App\Models\JobRefund;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  JobRefund::find($entity_id);
    }

    function synch($task, $refund)
    {
        QBRefund::actionDelete($refund);
        return $refund;
    }
}