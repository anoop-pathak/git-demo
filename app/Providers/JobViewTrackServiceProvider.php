<?php

namespace App\Providers;

use App\Services\JobViewTrack\JobViewTrack;
use Illuminate\Support\ServiceProvider;

class JobViewTrackServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('jobViewTrack', function ($app) {
            return new JobViewTrack;
        });
    }
}
