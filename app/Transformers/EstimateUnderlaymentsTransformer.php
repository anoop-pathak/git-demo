<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Request;

class EstimateUnderlaymentsTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['levels', 'images'];

	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($product) {
		$data = [
            'id'       		 =>  $product->id,
            'name'     		 =>  $product->name,
            'unit'     		 =>  $product->unit,
            'unit_cost'      =>  $this->getUnitCost($product),
            'code' 		     =>  $product->code,
            'description'    =>  $product->description,
            'selling_price'  =>  $this->getSellingPrice($product),
            'conversion_size' => $product->conversion_size,
        ];

        return $data;
	}

    public function includeLevels($product)
    {
        $estimateLevel = $product->levels;

        if($estimateLevel) {
            return $this->collection($estimateLevel, function($level){
                return [
                    'level_id' => $level->id,
                    'name' => $level->name,
                ];
            });
        }
    }

    public function includeImages($product)
    {
        $images = $product->images;

        if($images) {
            return $this->collection($images,  new FinancialProductImagesTransformer);
        }
    }

    private function getUnitCost($product)
    {
        if(!Request::has('for_sub_id')) {
            return $product->unit_cost;
        }

        if($product->labor_id != Request::get('for_sub_id')) {
            return null;
        }

        return $product->unit_cost;
    }

    private function getSellingPrice($product)
    {
        if(!Request::has('for_sub_id')) {
            return $product->selling_price;
        }

        if($product->labor_id != Request::get('for_sub_id')) {
            return null;
        }

        return $product->selling_price;
    }
}