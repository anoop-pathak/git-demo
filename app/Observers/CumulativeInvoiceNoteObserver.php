<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class CumulativeInvoiceNoteObserver
{
	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.creating: CumulativeInvoiceNote', 'App\Observers\CumulativeInvoiceNoteObserver@creating');
		$event->listen('eloquent.updating: CumulativeInvoiceNote', 'App\Observers\CumulativeInvoiceNoteObserver@updating');
	}

	//before created
	public function creating($cumulativeInvoiceNotes)
	{
		if(Auth::check()) {
			$cumulativeInvoiceNotes->created_by = Auth::id();
			$cumulativeInvoiceNotes->updated_by = Auth::id();
		}
	}

	//before updated
	public function updating($cumulativeInvoiceNotes)
	{
		$cumulativeInvoiceNotes->updated_by = Auth::id();
	}
}