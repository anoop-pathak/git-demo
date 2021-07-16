<?php namespace App\Providers;

use App\Services\Contexts\CompanyContext;
use Illuminate\Support\ServiceProvider;

class ContextServiceProvider extends ServiceProvider
{

    /**
     * Register
     */
    public function register()
    {
        $this->app->singleton('context', function ($app) {
            return new CompanyContext;
        });

        $this->app->bind(\App\Services\Contexts\Context::class, function ($app) {
            return $app['context'];
        });
    }
}
