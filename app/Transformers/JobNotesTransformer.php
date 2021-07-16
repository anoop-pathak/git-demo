<?php

namespace App\Transformers;

use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Transformers\AttachmentsTransformer;

class JobNotesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'created_by',
        'stage',
        'appointment'
    ];
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'created_by',
        'stage',
        'modified_by',
        'attachments',
        'attachments_count'
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
            'object_id' => $note->object_id,
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
            return $this->item($user, new UsersTransformerOptimized);
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
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include appointment
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAppointment($note)
    {
        $appointment = $note->appointment;
        if ($appointment) {
            return $this->item($appointment, new AppointmentsTransformer);
        }
    }

    /**
     * Include Attachments
     * @param  Instance $note Job Note
     * @return Attachments
     */
    public function includeAttachments($note)
    {
        $attachments = $note->attachments;
        if($attachments) {
            return $this->collection($attachments, new AttachmentsTransformer);
        }
    }

    /**
     * Include Attachments count
     * @param  Instance $note Job Note
     * @return Response
     */
    public function includeAttachmentsCount($note)
    {
        $count = $note->attachments()->count();

        return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
}
