<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Queue;

class CreateResourceDifferentSizeImages extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_resource_different_size_images';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create multi size images.';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
	   switchDBConnection('mysql2');
 	   start: {
	       Resource::whereYear('updated_at', '>=', 2018)
				->orderBy('updated_at', 'desc')
				->whereMultiSizeImages(false)
				->whereIn('mime_type', config('resources.image_types'))
				->chunk(1000, function($resources){
					foreach ($resources as $resource) {
						Queue::Connection('sqs_multi_image')->push('App\Handlers\Events\ResourceQueueHandler@createMultiSizeImage', [
							'id' => $resource->id,
						]);
					}
				});
	   }
		$resouceCount = Resource::whereYear('updated_at', '>=', 2018)
			->orderBy('updated_at', 'desc')
			->whereMultiSizeImages(false)
			->whereIn('mime_type', config('resources.image_types'))
			->count();
		if($resouceCount) {
			goto start;
		}
	}
 }