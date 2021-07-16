<?php

namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;

class MeasurementTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['created_by'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($measurement)
    {
        return [
            'id'        => $measurement->id,
            'job_id'    => $measurement->job_id,
            'file_name' => str_replace(' ', '_', $measurement->file_name),
            'url'  => $measurement->signed_url,
            'file_mime_type' => $measurement->file_mime_type,
            'file_size'  => $measurement->file_size,
            'created_at' => $measurement->created_at,
            'updated_at' => $measurement->updated_at,
        ];
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($measurement) {
        $user = $measurement->createdBy;
        if($user){
            return $this->item($user, new UsersTransformerOptimized);    
        }
    }
}
