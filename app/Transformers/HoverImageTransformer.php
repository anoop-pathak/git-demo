<?php 

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class HoverImageTransformer extends TransformerAbstract {
 	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];
 	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];
     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform ($image){
     	return [
    		'id'			=> $image->hover_image_id,
            'hover_job_id'  => $image->hover_job_id,
    		'company_id'	=> $image->company_id,
    		'url'      		=> \FlySystem::publicUrl(config('jp.BASE_PATH').$image->file_path),
    	];
    }
} 