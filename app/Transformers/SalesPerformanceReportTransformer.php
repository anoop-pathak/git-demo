<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SalesPerformanceReportTransformer extends TransformerAbstract {

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
	public function transform($user) {
		return [
            'id'                => $user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'full_name'         => $user->full_name,
            'full_name_mobile'  => $user->full_name_mobile,
            'leads_closed'      => $user->total_awarded_jobs,
            'lost_jobs'         => $user->total_lost_jobs,
            'amount'            => (float)$user->total_job_amount,
        ];
	}
}