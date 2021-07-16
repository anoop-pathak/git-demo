<?php

namespace App\Providers;

use App\Services\PushNotification\MobileNotification;
use Illuminate\Support\ServiceProvider;

class MobileNotificationServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('mobileNotification', function ($app) {
            return new MobileNotification;
        });
    }
}
