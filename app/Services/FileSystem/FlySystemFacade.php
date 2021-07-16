<?php

namespace App\Services\FileSystem;

use Illuminate\Support\Facades\Facade;

class FlySystemFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'flySystem';
    }
}
