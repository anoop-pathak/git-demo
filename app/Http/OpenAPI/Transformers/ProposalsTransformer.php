<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class ProposalsTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['created_by','job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($proposal)
    {
        $data = [
            'id'        => $proposal->id,
            'job_id'    => $proposal->job_id,
            'file_name' => str_replace(' ', '_', $proposal->file_name),
            'url'  => $proposal->signed_url,
            'file_mime_type' => $proposal->file_mime_type,
            'file_size'  => $proposal->file_size,
            'created_at' => $proposal->created_at,
            'updated_at' => $proposal->updated_at,
            'serial_number' => $proposal->serial_number,
        ];

        return $data;
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($proposal)
    {
        $user = $proposal->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($proposal)
    {
        $job = $proposal->job;
        if ($job) {
            return $this->item($job, new JobsTransformerOptimized);
        }
    }
}
