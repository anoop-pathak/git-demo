<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class WaterproofingTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['type'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($waterproofing)
    {
		return [
            'waterproofing_id'      => (int)$waterproofing->type_id,
            'cost'                  => (float)$waterproofing->cost,
            'cost_type'             => $waterproofing->cost_type,
            'type'                  => $waterproofing->type,
        ];
	}
}