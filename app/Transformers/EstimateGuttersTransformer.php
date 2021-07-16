<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class EstimateGuttersTransformer extends TransformerAbstract
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
	public function transform($estimateGutter) {
		$data = [
            'id'       		    =>  $estimateGutter->id,
            'size'              =>  $estimateGutter->size,
            'amount'            =>  (float)$estimateGutter->amount,
            'protection_amount' =>  (float)$estimateGutter->protection_amount,
        ];

        return $data;
	}
}