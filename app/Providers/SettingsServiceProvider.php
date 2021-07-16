<?php

namespace App\Providers;

use App\Services\Settings\Settings;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('settings', function ($app) {
            return new Settings();
        });

        $this->app->singleton('\App\Services\Settings\Setting', function ($app) {
            return $app['setting'];
        });
    }
}
