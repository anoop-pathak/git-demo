<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class JobCommissionsTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user',];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['job', 'paid_by', 'payments'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($commission)
    {
        return [
            'id' => $commission->id,
            'user_id' => $commission->user_id,
            'job_id' => $commission->job_id,
            'amount' => $commission->amount,
            'due_amount' => $commission->due_amount,
            'description' => $commission->description,
            'created_at' => $commission->created_at,
            'updated_at' => $commission->updated_at,
            'canceled_at' => $commission->canceled_at,
            'date' => $commission->date,
            'paid_on' => $commission->paid_on,
            'commission_percentage' => $commission->commission_percentage,
            'status' => $commission->status,
        ];
    }

    /**
     * Include User
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUser($commission)
    {
        $user = $commission->user;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($commission)
    {
        $job = $commission->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades', 'work_types', 'customer']);

            return $this->item($job, $transformer);
        }
    }

     /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includePayments($commission)
    {
        $payments = $commission->commissionPayment;
        if(!$payments->isEmpty()) {
            return  $this->collection($payments, new JobCommissionPaymentTransformer);
        }
    }

    /**
     * Include User
     *
     * @return League\Fractal\ItemResource
     */
    public function includePaidBy($commission)
    {
        $user = $commission->paidBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }
}
