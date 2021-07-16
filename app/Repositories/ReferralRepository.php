<?php

namespace App\Repositories;

use App\Models\Referral;
use App\Services\Contexts\Context;

class ReferralRepository extends ScopedRepository
{
    protected $scope;
    protected $model;

    function __construct(Referral $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    public function getReferrals($filters = array())
    {
        $referrals = $this->make()
            ->sortable()
            ->orderBy('name', 'asc');

        if(ine($filters, 'include_system_referral')) {
            $referrals->orWhere(function($query) {
                $query->where('company_id', 0);
            });
        }

        return $referrals;
    }

    public function saveReferral($data)
    {
        $data['company_id'] = $this->scope->id();
        $referral = $this->model->create($data);

        return $referral;
    }
}
