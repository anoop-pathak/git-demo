<?php
namespace App\Observers;

use QBDesktopQueue;

class WorksheetObserver
{
	// here is the listener
	public function subscribe( $event )
	{
		$event->listen('eloquent.deleted: App\Services\Model\Worksheet', 'App\Observers\WorksheetObserver@deleted');
    }

	// before creating
	public function deleted($worksheet) 
	{
		QBDesktopQueue::deleteWorksheet($worksheet);
	}
} 