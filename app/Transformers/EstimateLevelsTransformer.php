<?php
namespace App\Transformers;

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
    protected $availableIncludes = ['warranty'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($estimateLevel)
    {
		return [
            'level_id'      => $estimateLevel->id,
            'type'          => $estimateLevel->type,
            'fixed_amount'  => (float)$estimateLevel->fixed_amount,
        ];
	}

    public function includeWarranty($estimateLevel)
    {
        $warrantytypes = $estimateLevel->warranty;

        if($warrantytypes) {
            return $this->collection($warrantytypes, function($warranty){
                return [
                    'id' => $warranty->id,
                    'name' => $warranty->name,
                ];
            });
        }
    }
}