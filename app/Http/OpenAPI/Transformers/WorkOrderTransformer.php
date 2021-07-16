<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class WorkOrderTransformer extends TransformerAbstract
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
    public function transform($workOrder)
    {

        return [
            'id'         => $workOrder->id,
            'job_id'     => $workOrder->job_id,
            'file_name'  => str_replace(' ', '_', $workOrder->file_name),
            'url' => $workOrder->signed_url,
            'file_mime_type' => $workOrder->file_mime_type,
            'file_size'  => $workOrder->file_size,
            'created_at' => $workOrder->created_at,
            'updated_at' => $workOrder->updated_at,
            'serial_number' => $workOrder->serial_number,
        ];
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($workOrder)
    {
        $user = $workOrder->createdBy;

        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($workOrder)
    {
        $job = $workOrder->job;
        if ($job) {
            return $this->item($job, new JobsTransformerOptimized);
        }
    }
}
