<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SalePerformanceJobsWonTransformer extends TransformerAbstract
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
	public function transform($data)
    {
		return [
            'Sales Person Id' => $data['id'],
            'Sales Person' => $data['full_name'],
            'Lead(#)'      => $data['total_leads'],
            'Bids:Proposals(#)' => $data['total_bid_proposal_count'],
            'Bids:Jobs(#)' => $data['total_bid_proposal_jobs'],
            'Bids: Total Job Amount($)' => $data['total_bid_proposal_job_amount'],
            'Awarded Jobs(#)' => $data['total_awarded_jobs'],
            'Awarded Jobs: Total Job Amount' => $data['orig_contract_amount'],
            'Change Orders (#)' => $data['total_change_orders'],
            'Change Orders: Total Change Order Amount ($)' => $data['change_order_amount'],
            'Total Sales $' => $data['revised_contract_amount'],
        ];
	}
}