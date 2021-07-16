<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class RecentJobsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources to automatically include
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * transform an object into array
     * @param $job [object]
     * @return [array]
     */
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'number' => $job->number,
            'name'   =>  $job->name,
            'alt_id' => $job->alt_id,
            'division_code' =>  $job->division_code,
            'lead_number' => $job->lead_number,
            'customer_name' => $job->customer->full_name,
            'customer_id' => $job->customer_id,
            'current_stage' => $job->getCurrentStage(),
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        return $data;
    }
}
