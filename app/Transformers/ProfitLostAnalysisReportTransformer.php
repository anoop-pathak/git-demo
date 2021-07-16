<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer;
use App\Transformers\Optimized\UsersTransformer;
use League\Fractal\TransformerAbstract;

class ProfitLostAnalysisReportTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['customer'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['estimators'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $profitMargin = 0;

        $jobNet = $job->total_amount - $job->pl_sheet_total - $job->total_commission;
        if ((float)$job->total_amount) {
            $profitMargin = ($jobNet * 100) / $job->total_amount;
        }

        $data = [
            'id' => $job->id,
            'number' => $job->number,
            'alt_id' => $job->alt_id,
            'multi_job' => $job->multi_job,
            'created_date' => $job->created_date,
            'job_price' => numberFormat($job->total_job_amount),
            'change_order_amount' => numberFormat($job->total_change_order_amount),
            'account_payable_amount' => numberFormat($job->total_account_payable_amount),
            'total_job_price' => numberFormat($job->total_amount),
            'amount_received' => numberFormat($job->total_received_payemnt),
            'total_refunds'        => numberFormat($job->total_refunds),
            'total_credits'        => numberFormat($job->total_credits),
            'amount_owed' => numberFormat($job->pending_payment),
            'job_cost' => numberFormat($job->pl_sheet_total),
            'sales_commission' => numberFormat($job->total_commission),
            'job_net' => numberFormat($jobNet),
            'profit_margin' => numberFormat($profitMargin),
        ];

        return $data;
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
            $transformer = new CustomersTransformer;
            $transformer->setDefaultIncludes(['address']);

            return $this->item($customer, $transformer);
        }
    }

    /**
     * Include estimator
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEstimators($job)
    {
        $estimators = $job->estimators;
        if (!$estimators->isEmpty()) {
            return $this->collection($estimators, new UsersTransformer);
        }
    }
}
