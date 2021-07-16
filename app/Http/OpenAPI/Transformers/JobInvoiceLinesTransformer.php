<?php
namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;

class JobInvoiceLinesTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['work_type', 'trade'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($line) {

       return [
            'amount'        => $line->amount,
            'description'   => $line->description,
            'quantity'      => $line->quantity,
            'product_id'    => $line->product_id,
            'is_chargeable' => $line->is_chargeable,
        ];
    }

   
    /**
     * Include lines
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkType($line)
    {
        $workType = $line->workType;
        if ($workType) {
            return $this->item($workType, function ($workType) {
                return [
                    'id'    =>  $workType->id,
                    'name'  =>  $workType->name,
                    'color' =>  $workType->color,
                ];
            });
        }
    }

    public function includeTrade($line)
    {
        $trade = $line->trade;
        if ($trade) {
            return $this->item($trade, function ($trade) {
                return [
                    'id'    =>  $trade->id,
                    'name'  =>  $trade->name,
                ];
            });
        }
    }
}