<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Customer;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Models\Customer;

class UpdateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  Customer::find($entity_id);
    }

    function synch($task, $customer)
    {
        $customer = QBCustomer::qbSyncCustomer($customer->id, 'update');
        return $customer;
    }

}