<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TotalSalesReportTransformer extends TransformerAbstract
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
     public function transform($user)
     {
          return [
            'id'                            => $user->id,
            'first_name'                    => $user->first_name,
            'last_name'                     => $user->last_name,
            'full_name'                     => $user->full_name,
            'total_leads'                   => $user->total_leads,
            'total_bid_proposal_jobs'       => $user->total_bid_proposal_jobs,
            'total_bid_proposal_job_amount' => $user->total_bid_proposal_job_amount,
            'total_awarded_jobs'            => $user->total_awarded_jobs,
            'orig_contract_amount'          => $user->orig_contract_amount,
            'revised_contract_amount'       => $user->revised_contract_amount,
            'change_order_amount'           => $user->change_order_amount,
            'total_change_orders'           => (int) $user->total_change_orders,
            'total_bid_proposal_count'      => (int) $user->total_bid_proposal_count,
        ];
    }
} 