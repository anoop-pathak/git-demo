<?php

namespace App\Http\OpenAPI\Transformers;
 
use App\Http\OpenAPI\Transformers\JobTypesTransformer;

use App\Transformers\TradesTransformer as oTradesTransformer;

class TradesTransformer extends oTradesTransformer
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
    protected $availableIncludes = ['work_types'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($trade)
    {
        return [
            'id' => $trade->id,
            'name' => $trade->name,
            'color' => isset($trade->color) ? $trade->color : $trade->getDefaultColor(),
        ];
    }

    /**
     * Include Work Types
     *
     * @return League\Fractal\ItemResource
     */
    public function includeWorkTypes($trade)
    {
        $data = $trade->workTypes;

        $data = $this->collection($data, new JobTypesTransformer);

        return $data;
    }

}
