<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CustomServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('custom', function () {
            return new Demo1();
        });
    }
}
