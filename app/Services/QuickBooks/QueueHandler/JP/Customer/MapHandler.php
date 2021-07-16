<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Customer;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;

class MapHandler extends BaseTaskHandler
{
    private $qb_customer_id = null;

	function getEntity($entity_id)
    {
        return  Customer::find($entity_id);
    }

    function synch($task, $customer)
    {
            $meta = $task->payload;
            $qbCustomerId = ine($meta, 'qb_customer_id') ? $meta['qb_customer_id'] : null;
            $this->qb_customer_id = $qbCustomerId;
            $response = QBCustomer::get($qbCustomerId);

            if(!ine($response, 'entity')) {
                $task->markFailed("Not Found Error: Quickbook Customer not Found.", $this->queueJob->attempts());

                return $this->queueJob->delete();
            }

            QBCustomer::mapCustomerInQuickBooks($customer, $qbCustomerId);
            $customer = Customer::find($customer->id);

            return $customer;
    }

    protected function getSuccessLogMessage(){
		$format = "%s %s has been successfully mapped in QBO with %s";
		$message = sprintf($format, $this->task->object, $this->entity->getLogDisplayName(),  $this->qb_customer_id);
		Log::info($message);
		return $message;
	}
}