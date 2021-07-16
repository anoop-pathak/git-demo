<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;

class FinancialMacrosListTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'trade',
        'details',
        'total_product_count',
        'branch',
        'divisions'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($macro)
    {
        return [
            'macro_name' => $macro->macro_name,
            'type' => $macro->type,
            'macro_id' => $macro->macro_id,
            // 'supplier_id'    => $macro->supplier_id,
            'trade_id' => $macro->trade_id,
            'for_all_trades' => $macro->for_all_trades,
            'created_at' => $macro->created_at,
            'updated_at' => $macro->updated_at,
            'branch_code'    => $macro->branch_code,
            'order'          => $macro->order,
            'all_divisions_access' => $macro->all_divisions_access,
            'fixed_price' => $macro->fixed_price,
        ];
    }

    /**
     * Add trade include
     * @param  Object $macro Macro Object
     * @return Response
     */
    public function includeTrade($macro)
    {
        $trade = $macro->trade;
        if ($trade) {
            return $this->item($trade, new TradesTransformer);
        }
    }

    public function includeDetails($macro)
    {
        $products = $macro->details;
        if ($products) {
            $product = new FinancialProductsTransformer;
            $product->setDefaultIncludes([]);

            return $this->collection($products, $product);
        }
    }

    /**
     * Include total product count
     * @param  Instance $macro Macro
     * @return Resonse
     */
    public function includeTotalProductCount($macro)
    {
        $count = $macro->total_product;
        if($count) {

            return $this->item($count, function($count) {
                 return [
                    'count' => $count
                ];
            });
        }
    }

    /**
     * Include Supplier Branch
     *
     * @return League\Fractal\ItemResource
     */
    public function includeBranch($macro)
    {
        $branch = $macro->branch;
         if($branch) {
            return $this->item($branch, new SupplierBranchesTransformer);;
        }
    }

    /**
     * Include Division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivisions($macro)
    {
        $divisions = $macro->divisions;

        if(!$divisions->isEmpty()){

            return $this->collection( $divisions, new DivisionsTransformerOptimized);
        }
    }
}
