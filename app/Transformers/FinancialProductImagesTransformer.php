<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FinancialProductImagesTransformer extends TransformerAbstract
{
	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    public function transform($image)
	{
		$data = [
			'id' 		             => $image->id,
			'product_id'             => $image->product_id,
			'name'	 	             => $image->name,
			'size'			 		 => $image->size,
			'path' 		             => $image->path,
			'url'		             => $image->url,
			'thumb_url' 	         => $image->thumb_url,
			'mime_type'		         => $image->mime_type
		];

        return $data;
	}
}