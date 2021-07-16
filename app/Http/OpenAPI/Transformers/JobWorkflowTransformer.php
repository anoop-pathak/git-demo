<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\JobWorkflowTransformer as oJobWorkflowTransformer;

class JobWorkflowTransformer extends oJobWorkflowTransformer
{

    /**
     * List of resources possible to include
     *
     * @var array
     */

    protected $availableIncludes = [
        'modified_by'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($jobWorkflow)
    {
        return [
            'id' => $jobWorkflow->id,
            'stage_code' => $jobWorkflow->current_stage,
            'modified_by' => $jobWorkflow->modified_by,
            'stage_last_modified' => $jobWorkflow->stage_last_modified,
            'created_at' => $jobWorkflow->created_at,
            'updated_at' => $jobWorkflow->updated_at,
            'current_stage' => $jobWorkflow->stage,
            'last_stage_completed_date' => $jobWorkflow->last_stage_completed_date,
        ];
    }

    /**
     * Include modified_by
     *
     * @return League\Fractal\ItemResource
     */

	public function includeModifiedBy($jobWorkflow) {
        $user = $jobWorkflow->modified_by;
        $user = \App\Models\User::find($user);
        if($user) { 
			return $this->item($user, new UsersTransformer);
		}
	}
 
}
