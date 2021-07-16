<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;


class JobPriceRequestObserver {

	// here is the listener
    public function subscribe( $event )
    {
        $event->listen('eloquent.creating: JobPriceRequest', 'App\Observers\JobPriceRequestObserver@creating');
    }

    // before create ..
    public function creating($jobPriceRequest)
    {
        $jobPriceRequest->requested_by = Auth::id();
    }
}