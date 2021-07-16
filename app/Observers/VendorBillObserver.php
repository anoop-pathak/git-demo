<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class VendorBillObserver {

	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.deleting: VendorBill', 'App\Observers\VendorBillObserver@deleting');
		$event->listen('eloquent.creating: VendorBill', 'App\Observers\VendorBillObserver@creating');
		$event->listen('eloquent.updating: VendorBill', 'App\Observers\VendorBillObserver@updating');
	}

	//before delete
	public function deleting($vendorBill)
	{
		$vendorBill->deleted_by = Auth::id();
		$vendorBill->save();
	}

	//before created
	public function creating($vendorBill)
	{
		$vendorBill->created_by = Auth::id();
		$vendorBill->updated_by = Auth::id();
	}

	//before updated
	public function updating($vendorBill)
	{
		$vendorBill->updated_by = Auth::id();
	}
}