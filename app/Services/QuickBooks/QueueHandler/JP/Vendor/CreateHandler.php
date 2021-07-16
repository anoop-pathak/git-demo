<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Vendor;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Vendor as QBVendor;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\Vendor;


class CreateHandler extends BaseTaskHandler
{
    use CustomerAccountHandlerTrait;

	function getEntity($entity_id)
    {
        return  Vendor::find($entity_id);
    }

    function synch($task, $vendor)
    {
        QBVendor::actionCreate($vendor);
        $vendor = Vendor::find($vendor->id);

        return $vendor;
    }
}