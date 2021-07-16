<?php

namespace App\Repositories;

use App\Models\JobCommission;
use App\Services\Contexts\Context;

class CommissionsRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(JobCommission $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Get Filtered Commissions
     * @param  array $filters | Array for filters
     * @param  boolean $sortable | Boolean
     * @return query builder instance
     */
    public function getFiltredCommissions($filters = [], $sortable = true)
    {
        $commissions = $this->getCommissions($sortable);
        $this->applyFilters($commissions, $filters);

        return $commissions;
    }

    /**
     * Get Commissions
     * @param  boolean $sortable | Boolean
     * @return query builder instance
     */
    public function getCommissions($sortable = true)
    {
        if ($sortable) {
            $commissions = $this->make()->Sortable();
        } else {
            $commissions = $this->make();
        }

        return $commissions;
    }

    /************* Private Section ***************/

    private function applyFilters($query, $filters = [])
    {
        // by job_id
        if (ine($filters, 'job_id')) {
            $query->whereJobId($filters['job_id']);
        }

        // by user_ids
        if (ine($filters, 'user_ids')) {
            $query->whereIn('user_id', (array)$filters['user_ids']);
        }

        if(ine($filters, 'unpaid_commissions')){
            $query->where('due_amount', '>', 0);
        }

        // date range
        $jobDateRangeFilters = [
			'job_completion_date',
			'job_created_date',
			'job_awarded_Date',
			'contract_signed_date'
		];

        if(ine($filters, 'start_date') && ine($filters, 'end_date')
        	&& !(ine($filters, 'date_range_type') && in_array($filters['date_range_type'], $jobDateRangeFilters)) ) {
            $startDate = $filters['start_date'];
            $endDate = $filters['end_date'];

            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_commissions.created_at').", '%Y-%m-%d') >= '$startDate'");
            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_commissions.created_at').", '%Y-%m-%d') <= '$endDate'");
        }
    }
}
