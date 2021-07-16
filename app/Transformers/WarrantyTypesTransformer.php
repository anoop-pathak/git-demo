<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class WarrantyTypesTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['levels'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($warrantyType) {
		$data = [
            'id'       		 =>  $warrantyType->id,
            'manufacturer_id'=>  $warrantyType->manufacturer_id,
            'name'     	     =>  $warrantyType->name,
            'description'    =>  $warrantyType->description,
        ];

        return $data;
	}

    public function includeLevels($warrantyType)
    {
        $estimateLevel = $warrantyType->levels;

        if($estimateLevel) {
            return $this->collection($estimateLevel, function($level){
                return [
                    'level_id' => $level->id,
                    'name' => $level->name,
                ];
            });
        }
    }
}