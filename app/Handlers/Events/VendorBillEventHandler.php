<?php
namespace App\Handlers\Events;

use App\Models\JobFinancialCalculation;
use Illuminate\Support\Facades\Log;

class VendorBillEventHandler
{

	public function __construct()
	{

	}

	public function subscribe($event)
	{
		$event->listen('JobProgress.Events.VendorBillCreated', 'App\Handlers\Events\VendorBillEventHandler@created');
		$event->listen('JobProgress.Events.VendorBillUpdated', 'App\Handlers\Events\VendorBillEventHandler@updated');
		$event->listen('JobProgress.Events.VendorBillDeleted', 'App\Handlers\Events\VendorBillEventHandler@deleted');
		$event->listen('JobProgress.Events.VendorBillCreated', 'App\Handlers\Events\VendorBillEventHandler@updateJobBillAmount');
		$event->listen('JobProgress.Events.VendorBillUpdated', 'App\Handlers\Events\VendorBillEventHandler@updateJobBillAmount');
		$event->listen('JobProgress.Events.VendorBillDeleted', 'App\Handlers\Events\VendorBillEventHandler@updateJobBillAmount');
	}

	public function created( $event )
	{
		Log::info("Vendor bill event created.");
	}

	public function updated( $event )
	{
		Log::info("Vendor bill event updated.");
	}

	public function deleted( $event )
	{
		Log::info("Vendor bill event deleted.");
	}

	public function updateJobBillAmount( $event )
	{
		JobFinancialCalculation::updateJobFinancialbillAmount($event->vendorBill->job);
	}
}