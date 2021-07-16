<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\JobTypesTransformer as oJobTypesTransformer;

class JobTypesTransformer extends oJobTypesTransformer
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
    public function transform($jobType)
    {
        $data = [
            'id' => $jobType->id,
            'name' => $jobType->name,
            'trade_id' => $jobType->trade_id,
        ];

        if ($jobType->type == 2) {
            $data['color'] = $jobType->color;
        }

        return $data;
    }
}
