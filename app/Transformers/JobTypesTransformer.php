<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class JobTypesTransformer extends TransformerAbstract
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
    public function transform($jobType)
    {
        $data = [
            'id' => $jobType->id,
            'name' => $jobType->name,
            'trade_id' => $jobType->trade_id,
        ];

        if ($jobType->type == 1) {
            $data['insurance_claim'] = $jobType->insurance_claim;
        }

        if ($jobType->type == 2) {
            $data['color'] = $jobType->color;
            $data['qb_id'] = $jobType->qb_id;
            $data['qb_account_id'] = $jobType->qb_account_id;
        }

        return $data;
    }
}
