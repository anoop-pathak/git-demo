<?php 

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Masking\DataMasking;

class DataMaskingServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// $this->app['data_masking'] = $this->app->share(function($app)
		// {
		// 	return new DataMasking();
		// });
 		$this->app->singleton('dataMasking', function($app)
		{
			return new DataMasking();
		});
	}
} 
