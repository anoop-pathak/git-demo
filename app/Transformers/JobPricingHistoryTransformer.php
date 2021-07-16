<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOtimized;
use League\Fractal\TransformerAbstract;

class JobPricingHistoryTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['job', 'created_by'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($pricingHistory)
    {
        return [
            'id' => $pricingHistory->id,
            'amount' => $pricingHistory->amount,
            'taxable' => $pricingHistory->taxable,
            'tax_rate' => $pricingHistory->tax_rate,
            'custom_tax_id' => $pricingHistory->custom_tax_id,
            'created_at' => $pricingHistory->created_at,
            'updated_at' => $pricingHistory->updated_at,
        ];
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($pricingHistory)
    {
        $job = $pricingHistory->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades', 'work_types']);

            return $this->item($job, $transformer);
        }
    }

    /**
     * Include created by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($pricingHistory)
    {
        $user = $pricingHistory->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOtimized);
        }
    }
}
