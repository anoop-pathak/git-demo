<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ProjectSourceReportTransformer extends TransformerAbstract {
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
     public function transform($referral) {
      return [
        'id'                            => $referral->id,
        'name'                          => $referral->name,
        'total_leads'                   => $referral->total_leads,
        'total_bid_proposal_jobs'       => $referral->total_bid_proposal_jobs,
        'total_bid_proposal_job_amount' => $referral->total_bid_proposal_job_amount,
        'total_awarded_jobs'            => $referral->total_awarded_jobs,
        'total_awarded_jobs_amount'     => $referral->total_awarded_jobs_amount,
        'closing_rate'                  => numberFormat($referral->closing_rate),
        'total_rate'                    => numberFormat($referral->total_rate),
    ];
}
} 
