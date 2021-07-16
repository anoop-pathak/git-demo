<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class MarketSourceReportTransformer extends TransformerAbstract {
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
        'cost'                          => $referral->market_cost,
        'job_cost'                      => $referral->job_cost,
        'cost_per_win'                  => number_format($referral->cost_per_win, 2, '.',''),
        'cost_per_lead'                 => number_format($referral->cost_per_lead, 2, '.',''),
        'bad_leads'                     => $referral->bad_leads,
        'total_jobs_amount'             => number_format($referral->awarded_total_job_amount, 2, '.', ''),
        'leads_closed'                  => $referral->closed_leads,
        'avg_profit'                    => number_format($referral->avg_profit, 2, '.', ''),
        'avg_job_price'                 => number_format($referral->avg_job_price, 2, '.',''),
        'closing_rate'                  => number_format($referral->closing_rate, 2, '.',''),
    ];
}
} 