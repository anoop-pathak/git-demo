<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class JobFinancialCalculationTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($financial)
    {
        return [
            'id' => $financial->id,
            'job_id' => $financial->job_id,
            'total_job_amount' => $financial->total_job_amount,
            'total_change_order_amount' => $financial->total_change_order_amount,
            'total_amount' => $financial->total_amount,
            'total_received_payemnt' => $financial->total_received_payemnt,
            'pending_payment' => $financial->pending_payment,
            'total_commission' => $financial->total_commission,
            'total_account_payable_amount'    => $financial->total_account_payable_amount
        ];
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($financial)
    {
        $job = $financial->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades']);

            return $this->item($job, $transformer);
        }
    }
}
