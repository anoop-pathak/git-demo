<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Services\QuickBooks\Facades\Refund as QBRefund;

use App\Models\QuickBookTask;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;

class RefundEventHandler
{
	use CustomerAccountHandlerTrait;

    public function subscribe($event) {
		$event->listen('JobProgress.Refunds.Events.RefundCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\RefundEventHandler@createRefund');
		$event->listen('JobProgress.Refunds.Events.RefundCancelled', 'App\Services\QuickBooks\JPSystemEventHandlers\RefundEventHandler@deleteRefund');
	}

	public function createRefund($event)
	{
		$refund = $event->item;

		if(!$refund->getQBOId()){
			$this->resynchCustomerAccount($refund->customer_id, QuickBookTask::SYSTEM_EVENT);
		}
    }

	public function deleteRefund($event)
	{
		$refund = $event->item;

		if($refund->getQBOId()){
			$meta = [
				'id' => $refund->id,
				'customer_id' => $refund->customer_id,
				'company_id' => $refund->company_id,
			];
			QBRefund::createTask($refund->id,  QuickBookTask::DELETE, QuickBookTask::SYSTEM_EVENT, QuickBookTask::ORIGIN_JP, $meta);
		}
	}
}
