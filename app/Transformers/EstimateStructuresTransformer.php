<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EstimateStructuresTransformer extends TransformerAbstract
{
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
	public function transform($estimateStructure) {
		$data = [
            'type_id'         =>  $estimateStructure->type_id,
            'name'     		  =>  $estimateStructure->name,
            'type'       	  =>  $estimateStructure->type,
            'amount'    	  =>  (float)$estimateStructure->amount,
            'amount_type'     =>  $estimateStructure->amount_type,
            'icon'            =>  $estimateStructure->icon ? config('app.url').$estimateStructure->icon : null,
       ];

       return $data;
	}
}