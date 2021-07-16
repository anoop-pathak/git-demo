<?php

namespace App\Providers;

use App\Services\ActivityLogs;
use Illuminate\Support\ServiceProvider;

class ActivityLogsServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('activityLogs', function ($app) {
            return new ActivityLogs;
        });
    }
}
