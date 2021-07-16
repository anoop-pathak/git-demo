<?php

namespace App\Providers;

use App\Services\QuickBookDesktop\QBDesktopQueue;
use Illuminate\Support\ServiceProvider;

class QBDesktopQueueServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton('QBDesktopQueue', function () {
            return new QBDesktopQueue;
        });
    }
}
