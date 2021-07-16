<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EstimateTypeLayersTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['estimate_type'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($estimateTypeLayer)
    {
		return [
            'layer_id'               => $estimateTypeLayer->layer_id,
            'layers'                 => $estimateTypeLayer->layers,
            'cost'                   => (float)$estimateTypeLayer->cost,
            'cost_type'              => $estimateTypeLayer->cost_type,
        ];
    }

    /**
     * Include Estimate Type
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEstimateType($estimateTypeLayer)
    {
        $estimateType = $estimateTypeLayer->type;

        if($estimateType){
            return $this->item($estimateType, new EstimateTypesTransformer);
        }
    }
}