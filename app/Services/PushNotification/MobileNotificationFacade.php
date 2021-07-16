<?php

namespace App\Services\PushNotification;

use Illuminate\Support\Facades\Facade;

class MobileNotificationFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mobileNotification';
    }
}
