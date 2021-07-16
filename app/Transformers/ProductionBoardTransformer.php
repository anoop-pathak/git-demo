<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProductionBoardTransformer extends TransformerAbstract
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
    public function transform($pb)
    {

        return [
            'id' => $pb->id,
            'name' => $pb->name,
            'archived' => $pb->archived,
            'created_at' => $pb->created_at,
            'updated_at' => $pb->updated_at
        ];
    }
}
