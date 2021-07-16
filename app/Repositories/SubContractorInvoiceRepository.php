<?php

namespace App\Repositories;

use App\Models\SubContractorInvoice;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class SubContractorInvoiceRepository extends ScopedRepository
{

    protected $model;
    protected $scope;

    /**
     * Class Constructor
     * @param    $model
     * @param    $scope
     */
    public function __construct(SubContractorInvoice $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * create invoice
     * @param  $data
     * @return response
     */
    public function create($data)
    {
        $data['company_id'] = $this->scope->id();
        $data['user_id'] = \Auth::id();

        return $this->model->create($data);
    }

    /**
     * get invoice
     * @return $invoice object
     */
    public function getInvoices($filters)
    {
        $invoices = $this->make()
            ->whereCompanyId($this->scope->id())
            ->whereUserId(\Auth::id());

        $this->applyFilters($invoices, $filters);

        return $invoices;
    }

    protected function applyFilters($query, $filters = [])
    {
        if ($filters['job_id']) {
            $query->whereJobId($filters['job_id']);
        }

        if ($filters['schedule_id']) {
            $query->whereJobScheduleId($filters['schedule_id']);
        }
    }
}
