<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class RefundLinesTransformer extends TransformerAbstract {

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['trades', 'work_types'];

	/**
     * List of resources possible to include
     *
     * @var array
     */
    // protected $availableIncludes = ['financial_product'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($line) {

        return [
            'id'                   => $line->id,
            'financial_product_id' => $line->financial_product_id,
            'quantity'             => $line->quantity,
            'rate'                 => $line->rate,
            'description'          => $line->description,
        ];
    }

    /**
     * Include Financial Product
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFinancialProduct($line)
    {
        $financialProduct = $line->financialProduct;

        if($financialProduct) {
            return $this->item($financialProduct, function($financialProduct) {
                return[
                    'id' => $financialProduct->id,
                    'name'  => $financialProduct->name,
                    'unit_cost' => $financialProduct->unit_cost,
                    'selling_price' => $financialProduct->selling_price,
                    'description' => $financialProduct->description,
                ];
            });
        }
    }

    /**
     * Include Trades
     */
    public function includeTrades($line)
    {
        $trade = $line->trade;
        if($trade) {
            return $this->item($trade, function ($trade) {
                return [
                    'id' => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }

    /**
     * Include WorkTypes
     */
    public function includeWorkTypes($line)
    {
        $workType = $line->workType;
        if($workType) {
            return $this->item($workType, function ($workType) {
                return [
                    'id' => $workType->id,
                    'name' => $workType->name
                ];
            });
        }
    }
}