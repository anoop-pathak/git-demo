<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class VendorObserver {

	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.deleting: Vendor', 'App\Observers\VendorObserver@deleting');
		$event->listen('eloquent.creating: Vendor', 'App\Observers\VendorObserver@creating');
		$event->listen('eloquent.updating: Vendor', 'App\Observers\VendorObserver@updating');
	}

	//before delete
	public function deleting($vendor)
	{
		$vendor->deleted_by = Auth::id();
		$vendor->save();
	}

	//before created
	public function creating($vendor)
	{
		if(Auth::check()) {
			$vendor->created_by = Auth::id();
			$vendor->updated_by = Auth::id();
		}
	}

	//before updated
	public function updating($vendor)
	{
		$vendor->updated_by = Auth::id();
	}
}