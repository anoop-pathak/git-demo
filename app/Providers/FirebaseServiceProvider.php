<?php

namespace App\Providers;

use App\Services\Firebase\Firebase;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('firebase', function () {
            return new Firebase;
        });
    }
}
