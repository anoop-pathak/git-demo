<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Log;

class RefundEventHandler {

	public function subscribe($event)
	{
		$event->listen('JobProgress.Refunds.Events.RefundCreated', 'App\Handlers\Events\RefundEventHandler@refundCreated');
        $event->listen('JobProgress.Refunds.Events.RefundCancelled', 'App\Handlers\Events\RefundEventHandler@refundCancelled');
	}

	public function refundCreated($event)
	{
		Log::info(print_r($event, true));
	}

	public function refundCancelled($event)
	{
		Log::info(print_r($event, true));
	}
}