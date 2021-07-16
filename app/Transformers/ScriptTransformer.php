<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ScriptTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['trades'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($script)
    {
        return [
            'id' => $script->id,
            'title' => $script->title,
            'description' => $script->description,
            'type' => $script->type,
            'for_all_trades'=>  (bool)$script->for_all_trades,
            'created_at' => $script->created_at,
            'updated_at' => $script->updated_at,
        ];
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($script)
    {
        $trades = $script->trades;
        if($trades) {
            return $this->collection($trades, function($trade) {
                return [
                    'id'   => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }
}
