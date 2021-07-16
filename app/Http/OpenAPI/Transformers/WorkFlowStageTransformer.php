<?php

namespace App\Http\OpenAPI\Transformers;
  
use League\Fractal\TransformerAbstract;

class WorkFlowStageTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($state)
    {
        $data = [

            'id' => $state->id,
            'code' => $state->code,
            'workflow_id' => $state->workflow_id,
            'name' => $state->name,
            'locked' => $state->locked,
            'position' => $state->position,
            'color' => $state->color,
            'options' => $state->options,
            'created_at' => $state->created_at,
            'updated_at' => $state->updated_at,
            'jobs_count' => $state->jobs_count,
        ];

        return $data;
    }
}
