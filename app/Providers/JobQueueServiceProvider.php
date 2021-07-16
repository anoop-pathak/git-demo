<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Queue\JobQueue;

class JobQueueServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        $this->app->singleton('job_queue', function ($app) {
            return new JobQueue();
        });

        $this->app->singleton('\App\Services\Queue\JobQueue', function ($app) {
            return $app['job_queue'];
        });
	}
}