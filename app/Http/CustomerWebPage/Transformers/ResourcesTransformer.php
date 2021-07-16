<?php 
namespace App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;
use Request;

class ResourcesTransformer extends TransformerAbstract 
{

	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

	public function transform($resource)
	{
		$data = [
			'id' 		             => $resource->id,
			'name'	 	             => $resource->name,
			'url'		             => $resource->url,
			'mime_type'              => $resource->mime_type,
		];

		return $data;
	}
}