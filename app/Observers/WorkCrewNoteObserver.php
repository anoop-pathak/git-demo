<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class WorkCrewNoteObserver
{

    //here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting: WorkCrewNote', 'App\Observers\WorkCrewNoteObserver@deleting');
    }

    //before delete
    public function deleting($wcNote)
    {
        $wcNote->deleted_by = Auth::user()->id;
        $wcNote->save();
    }
}
