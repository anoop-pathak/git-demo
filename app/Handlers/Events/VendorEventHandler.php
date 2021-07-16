<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Log;

class VendorEventHandler
{

	public function __construct()
	{
	}

	public function subscribe($event)
	{
		$event->listen('JobProgress.Events.VendorCreated', 'App\Handlers\Events\VendorEventHandler@created');
		$event->listen('JobProgress.Events.VendorUpdated', 'App\Handlers\Events\VendorEventHandler@updated');
		$event->listen('JobProgress.Events.VendorDeleted', 'App\Handlers\Events\VendorEventHandler@deleted');
	}

	public function created( $event )
	{
		Log::info("Vendor event created.");
	}

	public function updated( $event )
	{
		Log::info("Vendor event updated.");
	}

	public function deleted( $event )
	{
		Log::info("Vendor event deleted.");
	}

}
