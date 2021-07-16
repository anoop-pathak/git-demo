<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class WorkflowStagesTransformer extends TransformerAbstract
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
    public function transform($stage)
    {

        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'code' => $stage->code,
            'color' => $stage->color,
            'locked' => $stage->locked,
            'position' => $stage->position,
            'workflow_id' => $stage->workflow_id,
            'resource_id' => $stage->resource_id,
            'options' => $stage->options,
            'send_customer_email' => (int)$stage->send_customer_email,
            'send_push_notification' => (int)$stage->send_push_notification,
            'create_tasks' => (int)$stage->create_tasks,
        ];
    }
}
