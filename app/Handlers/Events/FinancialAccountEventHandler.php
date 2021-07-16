<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Log;

class FinancialAccountEventHandler
{

	public function __construct()
	{
	}

	public function subscribe($event)
	{
		$event->listen('JobProgress.Events.FinancialAccountCreated', 'App\Handlers\Events\FinancialAccountEventHandler@created');
		$event->listen('JobProgress.Events.FinancialAccountUpdated', 'App\Handlers\Events\FinancialAccountEventHandler@updated');
		$event->listen('JobProgress.Events.FinancialAccountDeleted', 'App\Handlers\Events\FinancialAccountEventHandler@deleted');
	}

	public function created( $event )
	{
		Log::info("Financial account event created.");
	}

	public function updated( $event )
	{
		Log::info("Financial account event updated.");
	}

	public function deleted( $event )
	{
		Log::info("Financial account event deleted.");
	}

}