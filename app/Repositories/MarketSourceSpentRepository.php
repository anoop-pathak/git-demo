<?php

namespace App\Repositories;

use App\Models\MarketSourceSpent;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class MarketSourceSpentRepository extends ScopedRepository
{
    protected $scope;
    protected $model;

    function __construct(MarketSourceSpent $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    /**
     * get market source spents
     *
     * @return $spent
     */
    public function getSpents($filters)
    {
        $query = $this->make();

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * save market source spent
     *
     * @param $data [array]
     * @return $spent
     */
    public function saveSpent($data)
    {
        $data['company_id'] = $this->scope->id();
        $data['created_by'] = \Auth::id();
        $spent = $this->model->create($data);

        return $spent;
    }

    private function applyFilters($query, $filters)
    {
        //apply referral ids filter
        if (ine($filters, 'referral_ids') && is_array($filters['referral_ids'])) {
            $query->whereIn('referral_id', $filters['referral_ids']);
        }
    }
}
