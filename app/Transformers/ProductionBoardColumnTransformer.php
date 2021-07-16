<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProductionBoardColumnTransformer extends TransformerAbstract
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
    public function transform($pbColumn)
    {

        return [
            'id' => $pbColumn->id,
            'name' => $pbColumn->name,
            'board_id' => $pbColumn->board_id,
            'default' => $pbColumn->default,
            'sort_order' => $pbColumn->sort_order,
            'created_at' => $pbColumn->created_at,
            'updated_at' => $pbColumn->updated_at,
        ];
    }
}
