<?php
namespace App\Services\QuickBooks\QueueHandler\JP\CreditMemo;

use App\Services\QuickBooks\QueueHandler\BaseTaskHandler;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use Illuminate\Support\Facades\Log;
use App\Models\JobCredit;

class DeleteHandler extends BaseTaskHandler
{
	function getEntity($entity_id)
    {
        return  JobCredit::find($entity_id);
    }

    function synch($task, $jobCredit)
    {
        QBCreditMemo::deleteCreditNote($jobCredit);
        return $jobCredit;
    }

    protected function getSuccessLogMessage(){
		$format = "Credit for amount %s  has been cancelled in QBO";
		$message = sprintf($format, $this->entity->amount);
		Log::info($message);
		return $message;
	}
}