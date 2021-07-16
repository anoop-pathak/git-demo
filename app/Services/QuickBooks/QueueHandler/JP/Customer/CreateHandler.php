<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Customer;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Models\QuickbookUnlinkCustomer;
use App\Models\Customer;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;

class CreateHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  Customer::find($entity_id);
    }

    function synch($task, $customer)
    {
        $customer = QBCustomer::qbSyncCustomer($customer->id, 'create');

        //delete unlink entry if this customer was ever unlinked earlier
        if($customer->unlinkCustomer){
            QuickbookUnlinkCustomer::where('company_id', $customer->company_id)
                ->where('customer_id', $customer->id)
                ->delete();
        }

        return $customer;
    }
}