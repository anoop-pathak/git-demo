<?php

namespace App\Http\OpenAPI\Transformers;

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
            'job_id' => $financial->job_id,
            'total_job_price' => (float)$financial->total_job_amount,
            'total_change_order_amount' => (float)$financial->total_change_order_amount,
            'total_payment_received' => (float)$financial->total_received_payemnt,
            'total_amount_owed'    => (float)$financial->pending_payment,
            'total_credit_amount' => (float)$financial->total_credits,
            'total_refund_amount' => (float)$financial->total_refunds,
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
