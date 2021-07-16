<?php

namespace App\Services\JobViewTrack;

use Illuminate\Support\Facades\Facade;

class JobViewTrackFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'jobViewTrack';
    }
}
