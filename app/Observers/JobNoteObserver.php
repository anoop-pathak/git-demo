<?php
namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class JobNoteObserver
{
	public function subscribe( $event )
	{
		// $eventHandler = 'JobProgress\EventHandlers\ModelEvents\JobNoteModelEvent';

		// $event->listen('eloquent.deleting: JobNote', $eventHandler.'@deleting');
	}

	public function deleting($jobNote)
	{
		$jobNote->deleted_by = Auth::user()->id;
		$jobNote->save();
	}
}