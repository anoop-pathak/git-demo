<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class JobWorkflowHistoryTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['workflow_status'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($jobWorkflowHistory)
    {
        return [
            'id' => $jobWorkflowHistory->id,
            'stage' => $jobWorkflowHistory->stage,
            'start_date' => $jobWorkflowHistory->start_date,
            'completed_date' => $jobWorkflowHistory->completed_date,
            'modified_by' => $jobWorkflowHistory->modified_by,
            'approved_by' =>  $jobWorkflowHistory->approved_by,
            'created_at' => $jobWorkflowHistory->created_at,
            'updated_at' => $jobWorkflowHistory->completed_date,
        ];
    }

    /**
     * Include Workflow status
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStatus($jobWorkflowHistory)
    {
        return $this->item($jobWorkflowHistory, function ($jobWorkflowHistory) {
            return [
                'customer_email_sent' => $jobWorkflowHistory->isCustomerEmailSent(),
                'task_created' => $jobWorkflowHistory->isTaskCreated(),
            ];
        });
    }
}
