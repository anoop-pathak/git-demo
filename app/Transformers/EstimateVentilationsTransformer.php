<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EstimateVentilationsTransformer extends TransformerAbstract
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
	public function transform($estimateVentilation) {
		$data = [
            'type_id'                 =>  $estimateVentilation->type_id,
            'type'                    =>  $estimateVentilation->type,
            'fixed_amount'            =>  (float)$estimateVentilation->fixed_amount,
            'arithmetic_operation'    =>  $estimateVentilation->arithmetic_operation,
        ];

        return $data;
	}
}