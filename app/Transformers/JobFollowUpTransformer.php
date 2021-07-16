<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class JobFollowUpTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'stage',
        'task',
    ];
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'created_by',
        'stage',
        'task',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($followUp)
    {
        return [
            'id' => $followUp->id,
            'stage_code' => $followUp->stage_code,
            'note' => $followUp->note,
            'mark' => $followUp->mark,
            'order' => $followUp->order,
            'task_id' => $followUp->task_id,
            'date_time' => $followUp->date_time,
            'created_at' => $followUp->created_at,
            'job_id' => $followUp->job_id,
        ];
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($followUp)
    {
        $user = $followUp->user;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }

    /**
     * Include stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStage($followUp)
    {
        $stage = $followUp->stage()->first();
        if ($stage) {
            return $this->item($stage, function ($stage) {
                return [
                    'id' => $stage->id,
                    'code' => $stage->code,
                    'workflow_id' => $stage->workflow_id,
                    'name' => $stage->name,
                    'locked' => $stage->locked,
                    'position' => $stage->position,
                    'color' => $stage->color,
                ];
            });
        }
    }

    /**
     * Include task
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTask($followUp)
    {
        $task = $followUp->task;
        if ($task) {
            return $this->item($task, new TasksTransformer);
        }
    }
}
