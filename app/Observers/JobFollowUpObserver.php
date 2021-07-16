<?php
namespace App\Observers;
use Illuminate\Support\Facades\Auth;

class JobFollowUpObserver
{
	public function subscribe( $event )
	{
		// $eventHandler = 'JobProgress\EventHandlers\ModelEvents\JobFollowUpModelEvent';

		// $event->listen('eloquent.deleting: JobFollowUp', $eventHandler.'@deleting');
	}

	public function deleting($followUp)
	{
		$followUp->deleted_by = Auth::user()->id;
		$followUp->save();
	}
}

?>