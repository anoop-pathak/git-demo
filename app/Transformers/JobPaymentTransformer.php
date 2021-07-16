<?php

namespace App\Transformers;

use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use League\Fractal\TransformerAbstract;

class JobPaymentTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['job', 'transfer_from_payment','ref_job', 'transfer_to_payment'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($payment)
    {
        $data = [
            'id' => $payment->id,
            'customer_id' => $payment->customer_id,
            'job_id' => $payment->job_id,
            'canceled' => $payment->canceled,
            'method' => $payment->method,
            'payment' => $payment->payment,
            'echeque_number' => $payment->echeque_number,
            'serial_number' => $payment->serial_number,
            'status' => $payment->status,
            'created_by' => $payment->created_by,
            'modified_by' => $payment->modified_by,
            'date' => $payment->date,
            'quickbook_sync' => $payment->quickbook_sync,
            'cancel_note'    => $payment->cancel_note,
            'unapplied_amount' => $payment->unapplied_amount,
            'ref_id' => $payment->ref_id,
            'ref_to' => $payment->ref_to,
            'origin'  =>   $payment->originName(),
        ];

        return $data;
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($payment)
    {
        $job = $payment->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades', 'work_types']);

            return $this->item($job, $transformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTransferFromPayment($payment)
    {
        if(!$payment->ref_id) return;
        $transferPayment = $payment->transferFromPayment;
        if($transferPayment) {
            return $this->item($transferPayment, new JobPaymentTransformer);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTransferToPayment($payment)
    {
        if(!$payment->ref_to) return;
        $transferPayment = $payment->transferToPayment;
        if($transferPayment) {
            return $this->item($transferPayment, new JobPaymentTransformer);
        }
    }
    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeRefJob($payment)
    {
        $job = $payment->job;
        if($job) {
            return $this->item($job, function($job){
                return [
                    'id'            => $job->id,
                    'number'        => $job->number,
                    'name'          => $job->name,
                    'alt_id'        => $job->alt_id,
                    'multi_job'     => $job->multi_job,
                    'parent_id'     => $job->parent_id,
                ];
            });
        }
    }
}
