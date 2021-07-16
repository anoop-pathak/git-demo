<?php

namespace App\Http\OpenAPI\Transformers;

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
    protected $availableIncludes = ['job', 'transfer_from_payment', 'transfer_to_payment'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($payment)
    {
        $data = [
            'id'               => $payment->id,
            'customer_id'      => $payment->customer_id,
            'job_id'           => $payment->job_id,
            'canceled'         => $payment->canceled,
            'method'           => $payment->method,
            'payment'          => $payment->payment,
            'echeque_number'   => $payment->echeque_number,
            'serial_number'    => $payment->serial_number,
            'status'           => $payment->status,
            'date'             => $payment->date,
            'cancel_note'      => $payment->cancel_note,
            'unapplied_amount' => $payment->unapplied_amount,
            'ref_id'           => $payment->ref_id,
            'ref_to'           => $payment->ref_to,
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
}