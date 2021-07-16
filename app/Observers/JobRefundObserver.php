<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class JobRefundObserver
{
	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.created: JobRefund', 'App\Observers\JobRefundObserver@created');
	}

	// after created
	public function created($refund)
	{
		$refund->created_by = Auth::id();
		$refund->updated_by = Auth::id();

		$refund->save();
	}
}