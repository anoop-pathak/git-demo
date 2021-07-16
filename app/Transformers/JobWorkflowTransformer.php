<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class JobWorkflowTransformer extends TransformerAbstract
{

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
    public function transform($jobWorkflow)
    {
        return [
            'id' => $jobWorkflow->id,
            'current_stage_code' => $jobWorkflow->current_stage,
            'modified_by' => $jobWorkflow->modified_by,
            'stage_last_modified' => $jobWorkflow->stage_last_modified,
            'created_at' => $jobWorkflow->created_at,
            'updated_at' => $jobWorkflow->updated_at,
            'current_stage' => $jobWorkflow->stage,
            'last_stage_completed_date' => $jobWorkflow->last_stage_completed_date,
        ];
    }

    /**
     * Include Workflow status
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStatus($jobWorkflow)
    {
        return $this->item($jobWorkflow, function ($jobWorkflow) {
            return [
                'customer_email_sent' => $jobWorkflow->isCustomerEmailSent(),
                'task_created' => $jobWorkflow->isTaskCreated(),
            ];
        });
    }
}
