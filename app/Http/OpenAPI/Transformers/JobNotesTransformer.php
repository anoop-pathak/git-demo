<?php

namespace App\Http\OpenAPI\Transformers;

use App\Http\OpenAPI\Transformers\UsersTransformer;
use League\Fractal\TransformerAbstract;

class JobNotesTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'created_by',
        'modified_by',
        'stage',
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($note)
    {
        return [
            'id' => $note->id,
            'stage_code' => $note->stage_code,
            'note' => $note->note,
            'created_at' => $note->created_at,
            'updated_at' => $note->updated_at
        ];
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($note)
    {
        $user = $note->user;
        if ($user) {
            $transformer = new UsersTransformer;
            $transformer->setDefaultIncludes([]);
            $transformer->setAvailableIncludes([]);

            return $this->item($user, $transformer);
        }
    }

    /**
     * Include stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStage($note)
    {
        $stage = $note->stage;
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
     * Include modified_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeModifiedBy($note)
    {
        $user = $note->modifiedBy;
        if ($user) {
            $transformer = new UsersTransformer;
            $transformer->setDefaultIncludes([]);
            $transformer->setAvailableIncludes([]);

            return $this->item($user, $transformer);
        }
    }
}
