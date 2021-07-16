<?php
namespace App\Transformers\Optimized;

use League\Fractal\TransformerAbstract;

class EstimateLevelsTransformer extends TransformerAbstract
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
	public function transform($estimateLevel)
    {
        $type = $estimateLevel->levelType;

        return [
            'level_id'      => $type ? $type->id : null ,
            'type'          => $type ? $type->name : null,
            'fixed_amount'  => $estimateLevel->fixed_amount,
        ];
	}
}