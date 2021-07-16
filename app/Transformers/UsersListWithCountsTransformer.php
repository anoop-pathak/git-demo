<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class UsersListWithCountsTransformer extends TransformerAbstract
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

    public function transform($user)
    {
        return [
            'id' => (int)$user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'job_count_as_cr' => $user->customers->count(),
            // 'job_count_as_jr'        =>	 $user->allJobsAsRepOrEstimator()->count(),
            'job_count_as_estimator' => $user->jobsAsEstimator->count(),
            'job_count_as_jr' => $user->jobsAsRep->count(),
        ];
    }
}
