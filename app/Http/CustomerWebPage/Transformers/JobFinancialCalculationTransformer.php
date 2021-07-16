<?php 
namespace App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;

class JobFinancialCalculationTransformer extends TransformerAbstract
{
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
	public function transform($financial)
    {
		return [
            'id'                        => $financial->id,
            'job_id'                    => $financial->job_id,
            'job_amount'                => $financial->job->amount,
            'tax_rate'                  => $financial->job->tax_rate,
            'total_job_amount'          => $financial->total_job_amount,
            'taxable'                   => $financial->job->taxable,
            'total_change_order_amount' => $financial->total_change_order_amount,
            'total_amount'              => $financial->total_amount, 
            'total_received_payemnt'    => $financial->total_received_payemnt,
            'pending_payment'           => $financial->pending_payment,
            'total_credits'             => $financial->total_credits
        ];
	}
}