<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\MeasurementFormulaTransformer;
use App\Transformers\QBDesktopQueueTransformer;
use App\Transformers\FinancialProductImagesTransformer;
use App\Transformers\TradesTransformer;
use Request;

class FinancialProductsTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['measurement_formulas', 'measurement_formulas_count', 'labor', 'qbd_queue_status', 'images', 'trade'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($product)
    {

        $data = [
            'id' => $product->id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'unit' => $product->unit,
            'unit_cost' =>  $this->getUnitCost($product),
            'code' => $product->code,
            'description' => $product->description,
            'category' => $product->category,
            'labor_id' => $product->labor_id,
            'selling_price'  =>  $this->getSellingPrice($product),
            'supplier_id' => $product->supplier_id,
            'supplier' => $product->supplier,
            'styles' => $product->styles,
            'sizes' => $product->sizes,
            'colors' => $product->colors,
            'affected_from' => $product->affected_from,
            'abc_additional_data' => $product->abc_additional_data,
            'branch' => $product->branch,
            'branch_code' => $product->branch_code,
            'branch_logo' => $product->branch_logo,
            'qb_desktop_id' => $product->qb_desktop_id,
            'active'              => $product->active,
            'alternate_units'       => $product->alternate_units,
        ];

        if ($pivot = $product->pivot) {
            $data['order'] = $pivot->order;
            $data['quantity'] = $pivot->quantity;
        }

        return $data;
    }

    /**
     * Include Labor
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLabor($product)
    {
        $labor = $product->labor;
        if ($labor) {
            return $this->item($labor, new LabourTransformer);
        }
    }

    public function includeMeasurementFormulas($product)
    {
        $formulas = $product->measurementFormulas;
        $formulaTrans = new MeasurementFormulaTransformer;
        $formulaTrans->setDefaultIncludes([]);

        return $this->collection($formulas, $formulaTrans);
    }
     public function includeMeasurementFormulasCount($product)
    {
        $count = $product->measurement_formulas_count;
        return $this->item($count, function($count){
            return [
                'count' => $count
            ];
        });
    }

    public function includeQbdQueueStatus($product)
    {
        $qbdQueue = $product->qbDesktopQueue;
        if($qbdQueue) {
            return $this->item($qbdQueue, new QBDesktopQueueTransformer);
        }
    }

    public function includeImages($product)
    {
        $images = $product->images;
        if($images) {
            return $this->collection($images,  new FinancialProductImagesTransformer);
        }
    }

    public function includeTrade($product)
    {
        $trade = $product->trade;

        if($trade) {
            $defaultTrans = new TradesTransformer;
            $defaultTrans->setDefaultIncludes([]);

            return $this->item($trade, $defaultTrans);
        }
    }

    private function getUnitCost($product)
    {
        if(!Request::has('for_sub_id')) return $product->unit_cost;
        if($product->labor_id != Request::get('for_sub_id')) return null;
        return $product->unit_cost;
    }
    private function getSellingPrice($product)
    {
        if(!Request::has('for_sub_id')) return $product->selling_price;
        if($product->labor_id != Request::get('for_sub_id')) return null;
        return $product->selling_price;
    }
}
