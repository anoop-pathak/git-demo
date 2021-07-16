<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class ReportJobListingTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['customer', 'financial_details'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['address', 'job_workflow'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'name' => $job->name,
            'number' => $job->number,
            'trades' => $job->trades,
            'description' => $job->description,
            'same_as_customer_address' => $job->same_as_customer_address,
            'other_trade_type_description' => $job->other_trade_type_description,
            'created_by' => $job->created_by,
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'updated_at' => $job->updated_at,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'current_stage' => $job->getCurrentStage(),
            'archived' => $job->archived,
            'job_awarded_date'         =>   $job->getSoldOutDate(),
            'contract_signed_date'     =>   $job->getContractSignedDate(),
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        if ($job->isMultiJob()) {
            $projectIds = $job->projects->pluck('id')->toArray();
            $data['projects_count'] = count($projectIds);
            $data['project_ids'] = $projectIds;
        }

        return $data;
    }

    /**
     * Include Financial Details
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFinancialDetails($job)
    {
        $financialCalculation = $job->financialCalculation;

        return $this->item($financialCalculation, function ($financialCalculation) use ($job) {
            return [
                'total_job_amount' => numberFormat($financialCalculation->total_job_amount),
                'total_change_order_amount' => numberFormat($financialCalculation->total_change_order_amount),
                'total_amount' => numberFormat($financialCalculation->total_amount),
                'total_received_payemnt' => numberFormat($financialCalculation->total_received_payemnt),
                'total_credits' => numberFormat($financialCalculation->total_credits),
                'total_refunds' => numberFormat($financialCalculation->total_refunds),
                'pending_payment' => numberFormat($financialCalculation->pending_payment),
                'total_commission' => numberFormat($financialCalculation->total_commission),
                'sold_out_date' => $job->getSoldOutDate(),
                'can_block_financials' => (int)$job->canBlockFinacials(),
            ];
        });
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
            return $this->item($customer, new CustomersTransformerOptimized);
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
     * Include JobWorkflow
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobWorkflow($job)
    {
        $jw = $job->jobWorkflow;

        if ($jw) {
            return $this->item($jw, new JobWorkflowTransformer);
        }
    }
}
