<?php

namespace App\Repositories;

use App\Models\CompanyNetwork;
use App\Services\Contexts\Context;

class CompanyNetworksRepository extends ScopedRepository
{

    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;

    public function __construct(CompanyNetwork $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    public function save($token, $network)
    {
        $this->model->token = $token;
        $this->model->network = $network;
        $this->model->company_id = $this->scope->id();
        $this->model->save();
    }

    public function findBy($attribute, $value, $column = ['*'])
    {
        return $this->make()->where($attribute, '=', $value)->first();
    }

    public function delete($attribute, $value)
    {
        $companyNetworkDelete = $this->model->where($attribute, '=', $value)
            ->where('company_id', $this->scope->id())
            ->firstOrFail();
        $companyNetworkDelete->delete();
    }

    public function get()
    {
        return $this->model->where('company_id', $this->scope->id())
            ->get();
    }

    public function getToken($network)
    {
        return $this->make()->whereHas('company', function ($query) use ($network) {
            $query->whereNetwork($network);
        })->select('token', 'id')->first();
    }

    public function token($network)
    {
        return $this->getToken($network);
    }

    public function companyId()
    {
        return $this->scope->id();
    }
}
