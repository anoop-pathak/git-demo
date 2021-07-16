<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;

class JobCreditTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['job', 'invoice'];

    public function transform($credit)
    {

        return [
            'id' => $credit->id,
            'company_id' => $credit->company_id,
            'customer_id' => $credit->customer_id,
            'job_id' => $credit->job_id,
            'file_size' => $credit->file_size,
            'amount' => $credit->amount,
            'unapplied_amount' =>  $credit->unapplied_amount,
            'method' => $credit->method,
            'status'           =>  $credit->status,
            'echeque_number' => $credit->echeque_number,
            'note' => $credit->note,
            'date' => $credit->date,
            'quickbook_id' => $credit->quickbook_id,
            'canceled' => $credit->canceled,
            'created_at' => $credit->created_at,
            'updated_at' => $credit->updated_at,
            'origin'     =>  $credit->originName(),
        ];
    }


    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($credit)
    {
        $job = $credit->job;

        if ($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes(['trades', 'work_types']);

            return $this->item($job, $transformer);
        }
    }

    /* Include invoice
     *
     * @return League\Fractal\CollectionResource
     */
    public function includeInvoice($credit)
    {
        $invoices = $credit->invoices;
        if($invoices) {
            return $this->collection($invoices, function($invoice){
                return [
                    'id'          => $invoice->id,
                    'number'      => $invoice->invoice_number,
                    'title'       => $invoice->title,
                    'file_path'   => FlySystem::publicUrl(config('jp.BASE_PATH'). $invoice->file_path),
                    'name'        => $invoice->name,
                    'type'        => $invoice->type,
                ];
            });
        }
    }
}
