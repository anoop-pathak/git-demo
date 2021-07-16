<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EstimationServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('estimation', function () {
            return new Demo1;
        });
    }
}
