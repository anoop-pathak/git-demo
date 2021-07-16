<?php

namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\Facade;

class QBDesktopQueueFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'QBDesktopQueue';
    }
}
