<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\TaskManager\TaskManager;

class QuickBookDesktopServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->singleton('qbd-task-manager', function () {
            return new TaskManager;
        });

        $this->app->singleton('qbd-task-scheduler', function () {
            return new TaskScheduler;
        });
    }
}