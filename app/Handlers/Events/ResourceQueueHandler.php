<?php

namespace App\Handlers\Events;

use App\Models\Resource;
use Image;
use FlySystem;
use Exception;
use DB;
use Log;

class ResourceQueueHandler
{
	
	public function createMultiSizeImage($queueJob, $data = array())
	{
		$resource = Resource::where('id', $data['id'])
						->whereMultiSizeImage(false)
						->whereIn('mime_type', config('resources.image_types'))
						->first();
		if(!$resource) return $queueJob->delete();
 		try {
			$fullPath = config('resources.BASE_PATH').$resource->path;
			$img = FlySystem::read($fullPath);
			$sizes = config('resources.multi_image_width');
			foreach ($sizes as $size) {
				$this->createImage($img, $size, $resource->path);
			}
 			if(!$resource->thumb_exists) {
				$this->createThumb($img, $resource);
			}
 			Resource::where('id', $resource->id)
				->update([
					'multi_size_image'	=> true,
					'thumb_exists'		=> true,
				]);
 			$queueJob->delete();
		} catch(\League\Flysystem\FileNotFoundException $e) {
			$queueJob->delete();
		} catch(Exception $e) {
			Log::error($e);
		}
 	}
 	public function createImage($image, $size, $path)
	{
		$image = \Image::make($image);
	    $image->widen($size, function($constraint) {
	       $constraint->upsize();
	    });
 		//image path
		$path = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', "_pv{$size}$1", $path);
 		// upload image..
		FlySystem::put(config('resources.BASE_PATH').$path, $image->encode()->getEncoded());
	}
 	public function createThumb($image, $resource)
	{
		$image = \Image::make($image);
 		if($image->height() > $image->width()) {
			$image->heighten(200, function($constraint) {
		    	$constraint->upsize();
		   	}); 
		}else {
		    $image->widen(200, function($constraint) {
		       $constraint->upsize();
		    });
		}
 		// add thumb suffix in filename for thumb name
		$thumbName = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $resource->path);
 		FlySystem::put(config('resources.BASE_PATH').'/'.$thumbName, $image->encode()->getEncoded());
		
		$resource->thumb_exists = true;
		$resource->save();
	}
} 
