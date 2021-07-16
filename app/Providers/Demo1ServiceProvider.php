<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class Demo1ServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('demo1', function () {
            return new Demo1;
        });
    }
}
