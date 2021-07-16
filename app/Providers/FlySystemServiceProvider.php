<?php

namespace App\Providers;

use App\Services\FileSystem\FlySystem;
use Illuminate\Support\ServiceProvider;

class FlySystemServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flySystem', function ($app) {
            return new FlySystem();
        });
    }
}
