<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class MaterialListTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['created_by', 'job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($materialList)
    {

        return [
            'id'        => $materialList->id,
            'job_id'    => $materialList->job_id,
            'file_name' => str_replace(' ', '_', $materialList->file_name),
            'url'  => $materialList->signed_url,
            'file_mime_type' => $materialList->file_mime_type,
            'file_size'  => $materialList->file_size,
            'created_at' => $materialList->created_at,
            'updated_at' => $materialList->updated_at,
            'serial_number'  => $materialList->serial_number,
        ];
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($materialList)
    {
        $user = $materialList->createdBy;

        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($materialList)
    {
        $job = $materialList->job;
        if ($job) {
            return $this->item($job, new JobsTransformerOptimized);
        }
    }
}
