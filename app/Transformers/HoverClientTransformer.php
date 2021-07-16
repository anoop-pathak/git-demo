<?php 

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class HoverClientTransformer extends TransformerAbstract {
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
    public function transform ($hover){
     	return [
    		'id'		=> $hover->id,
            'owner_id'  => $hover->owner_id,
    	];
    }
} 