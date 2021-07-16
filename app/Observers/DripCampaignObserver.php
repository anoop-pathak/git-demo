<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class DripCampaignObserver {

	//here is the listener
	public function subscribe( $event ){
		$event->listen('eloquent.creating: DripCampaign', 'App\Observers\DripCampaignObserver@creating');
	}

	//before created
	public function creating($dripCampaign)
	{
		if(Auth::check()) {
			$dripCampaign->created_by = Auth::id();
		}
	}
}