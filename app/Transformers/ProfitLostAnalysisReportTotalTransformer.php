<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProfitLostAnalysisReportTotalTransformer extends TransformerAbstract
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
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($job)
    {
        $jobNet = $job->job_net;
        $profitMargin = 0;

        if((float)$job->amount_owed_total) {
            $profitMargin = ($jobNet * 100) / $job->amount_owed_total;
        }

        $data = [
            'job_price_total'           => numberFormat($job->job_price_total),
            'change_order_amount_total' => numberFormat($job->change_order_amount_total),
            'total_job_price_total'     => numberFormat($job->total_job_price_total),
            'amount_received_total'     => numberFormat($job->amount_received_total),
            'amount_owed_total'         => numberFormat($job->amount_owed_total),
            'job_cost_total'            => numberFormat($job->job_cost_total),
            'sales_commission_total'    => numberFormat($job->sales_commission_total),
            'job_net'                   => numberFormat($job->job_net),
            'profit_margin'             => numberFormat($profitMargin),
            'total_credits'             => numberFormat($job->total_credits),
            'total_refunds'             => numberFormat($job->total_refunds),
		];

        return $data;
	}
}