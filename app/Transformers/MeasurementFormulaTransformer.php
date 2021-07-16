<?php

namespace App\Transformers;

use App\Models\Trade;
use League\Fractal\TransformerAbstract;

class MeasurementFormulaTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['trade'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['options'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($formula)
    {

        return [
            'id' => $formula->id,
            'trade_id' => $formula->trade_id,
            'product_id' => $formula->product_id,
            'formula' => $formula->formula,
        ];
    }

    /**
     * Include Trade
     * @param  trade $formula Trade
     * @return Trade
     */
    public function includeTrade($formula)
    {
        $trade = $formula->trade;
        if ($trade) {
            $trans = new TradesTransformer;
            $trans->setDefaultIncludes([]);

            return $this->item($trade, $trans);
        }
    }

    public function includeOptions($formula)
    {
        return $this->item($formula, function($formula) {
             return  ($formula->options) ?: [];
        });
    }
}
