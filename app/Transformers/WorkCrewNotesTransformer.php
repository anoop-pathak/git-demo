<?php

namespace App\Transformers;

use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class WorkCrewNotesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'labours',
        'sub_contractors',
        'reps'
    ];

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
    public function transform($wcNote)
    {

        return [
            'id' => $wcNote->id,
            'job_id' => $wcNote->job_id,
            'note' => $wcNote->note,
            'created_at' => $wcNote->created_at,
            'updated_at' => $wcNote->updated_at
        ];
    }

    /**
     * Include labours
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLabours($wcNote)
    {
        $labours = [];

        // suppprt old mobile app to manage labor after labor enhancement
        return $this->collection($labours, function () {
            return [];
        });
    }

    /**
     * Include sub_contractors
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSubContractors($wcNote)
    {
        $subContractors = $wcNote->subContractors;
        if ($subContractors) {
            return $this->collection($subContractors, new LabourTransformer);
        }
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($wcNote)
    {
        $reps = $wcNote->reps;
        if ($reps) {
            return $this->collection($reps, new UsersTransformerOptimized);
        }
    }
}
