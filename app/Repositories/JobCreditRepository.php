<?php namespace App\Repositories;

use App\Models\JobCredit;
use App\Services\Contexts\Context;

class JobCreditRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(JobCredit $model, Context $scope)
    {

        $this->model = $model;
        $this->scope = $scope;
    }

    public function create($input)
    {
        $input['company_id'] = $this->scope->id();
        return $this->model->create($input);
    }

    public function getFilteredJobCredit($filters)
    {
        $jobCredit = $this->make();
        $this->applyFilters($jobCredit, $filters);

        return $jobCredit;
    }

    private function applyFilters($query, $filters)
    {

        if (ine($filters, 'customer_id')) {
            $query->whereCustomerId($filters['customer_id']);
        }

        if (ine($filters, 'job_id')) {
            $query->whereJobId($filters['job_id']);
        }

        if(ine($filters, 'unapplied_only')) {
            $query->where('unapplied_amount', '>', 0);
            $query->whereNull('ref_id');
			$query->whereNull('ref_to');
			$query->excludeCanceled();
        }
    }
}
