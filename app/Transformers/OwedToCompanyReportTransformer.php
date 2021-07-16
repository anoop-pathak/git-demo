<?php

namespace App\Transformers;

use App\Models\Job;
use App\Models\JobCommission;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Transformers\Optimized\CustomersTransformer;
use Request;
use League\Fractal\TransformerAbstract;
use App\Transformers\DivisionTransformer;

class OwedToCompanyReportTransformer extends TransformerAbstract
{

    protected $projectsIds = [];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'customer',
        // 'address',
        // 'payments',
        'payment_methods',
        'invoices',
        'commissions',
        'trades',
        'work_type',
        'division'
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
    public function transform($job)
    {

        if ($job->isMultiJob()) {
            $this->projectsIds = $job->projects->pluck('id')->toArray();
        }

        $amount = $job->total_amount;
        $pendingPayment = $job->pending_payment;

        //show invoice wise amount
        if (Request::get('use_invoice_amount')) {
            $amount = $job->total_invoice_amount;
            $pendingPayment = $job->total_invoice_amount - ($job->total_received_payemnt + $job->total_credits);
        }

        return [
            'id' => $job->id,
            'name' => $job->name,
            'number' => $job->number,
            'alt_id' => $job->alt_id,
            'customer_id' => $job->customer_id,
            'completion_date' => $job->completion_date,
            'archived' => $job->archived,
            'total_payemnt' => numberFormat($amount),
            'recieved_payemnt' => numberFormat($job->total_received_payemnt),
            'pending_payemnt' => numberFormat($pendingPayment),
            'sold_out_date' => $job->getSoldOutDate(),
            'ageing' => $job->getAgeing(),
            'multi_job' => $job->multi_job,
            'total_credits'    =>   numberFormat($job->total_credits),
            'division_code'    =>   $job->division_code,
            'total_refunds'    =>   numberFormat($job->total_refunds),
        ];
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {
            return $this->item($customer, new CustomersTransformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($job)
    {
        $address = $job->address;
        if ($address) {
            return $this->item($address, new AddressesTransformer);
        }
    }

    /**
     * Include Payments
     * @todo Eagerload payments for multi job with projects
     * @return League\Fractal\ItemResource
     */
    public function includePayments($job)
    {
        if ($job->isMultiJob()) {
            $ids = $this->projectsIds;
            $ids[] = $job->id;
            $payments = JobPayment::whereIn('job_id', $ids)
                ->excludeCanceled()
                ->get();
        } else {
            $payments = $job->payments;
        }

        return $this->collection($payments, function ($payment) {

            return [
                'id' => $payment->id,
                'payment' => $payment->payment,
                'method' => $payment->method,
                'echeque_number' => $payment->echeque_number,
                'status' => $payment->status,
                'date' => $payment->date,
                'ref_to' => $payment->ref_to,
                'ref_id' => $payment->ref_id,
            ];
        });
    }

    /**
     * Include Invoices
     * @todo Eagerload invoices for multi job with projects
     * @return League\Fractal\ItemResource
     */
    public function includeInvoices($job)
    {
        if ($job->isMultiJob()) {
            $ids = $this->projectsIds;
            $ids[] = $job->id;
            $invoices = JobInvoice::whereIn('job_id', $ids)->get();
        } else {
            $invoices = $job->invoices;
        }
        $transformer = new JobInvoiceTransformer;
        $transformer->setDefaultIncludes([]);
        return $this->collection($invoices, $transformer);
    }

    /**
     * Include Commissions
     * @todo Eager load commissions for multi job with projects
     * @return League\Fractal\ItemResource
     */
    public function includeCommissions($job)
    {
        if ($job->isMultiJob()) {
            $ids = $this->projectsIds;
            $ids[] = $job->id;
            $commissions = JobCommission::whereIn('job_id', $ids)->whereNull('canceled_at')->get();
        } else {
            $commissions = $job->commissions()->whereNull('canceled_at')->get();
        }

        return $this->collection($commissions, new JobCommissionsTransformer);
    }

    public function includeTrades($job)
    {
        $trades = $job->trades;
        return $this->collection($trades, function($trades){
            return [
                'id'    => $trades->id,
                'name'  => $trades->name,
            ];
        });
    }

    public function includeWorkType($job)
    {
        $worktype = $job->workTypes;
        return $this->collection($worktype, function($worktype){
            return [
                'id'  => $worktype->id,
                'name'=> $worktype->name,
            ];
        });
    }

    public function includePaymentMethods($job)
    {
        $paymentMethods = $job->paymentMethods;

        return $this->collection($paymentMethods, function($paymentMethods){

            return [
                'name'=> $paymentMethods->method,
            ];
        });
    }

    public function includeDivision($job)
    {
        $divisions = $job->division;
        if($divisions) {

            return $this->item($divisions, new DivisionTransformer);
        }
    }

    /******************** Private function ********************/

    /**
     * @param object | Job class instance
     *
     * @return array
     */
    private function getJobMeta($job)
    {
        $meta = $job->jobMeta;

        if ($meta) {
            $metaData = [];
            foreach ($meta as $key => $value) {
                $metaData[$value['meta_key']] = $value['meta_value'];
            }
        }
        return $metaData;
    }

    /**
     * @param object | Job Model instance
     *
     * @return array | current stage data
     */
    private function getCurrentStage($job)
    {
        $ret = ['name' => 'Unknown', 'color' => 'black', 'code' => null, 'resource_id' => null];
        try {
            $currentStage = [];
            $jobWorkflow = $job->jobWorkflow;
            if (is_null($jobWorkflow)) {
                return $ret;
            }
            $stage = $jobWorkflow->stage;
            $currentStage['name'] = $stage->name;
            $currentStage['color'] = $stage->color;
            $currentStage['code'] = $stage->code;
            $currentStage['resource_id'] = $stage->resource_id;
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }
}
