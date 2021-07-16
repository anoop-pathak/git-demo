<?php
namespace App\Services\QuickBooks\QueueHandler\JP\Payment;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Models\JobPayment;
use Illuminate\Support\Facades\Log;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return JobPayment::find($entity_id);
    }

    function synch($task, $jobPayment)
    {
        // delete payment on QB
        QBPayment::qbDeletePayment($jobPayment);
        return $jobPayment;
    }

    protected function getSuccessLogMessage(){
		$format = "Payment transaction for amount %s  has been cancelled on QBO";
		$message = sprintf($format, $this->entity->payment);
		Log::info($message);
		return $message;
	}
}