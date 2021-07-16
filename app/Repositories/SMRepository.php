<?php

namespace App\Repositories;

use App\Models\SMClient;
use App\Models\SMOrder;
use App\Services\Contexts\Context;

class SMRepository extends ScopedRepository
{

    protected $scope;

    function __construct(Context $scope)
    {
        $this->scope = $scope;
    }

    public function saveClient($username, $token)
    {
        $client = SMClient::whereCompanyId($this->scope->id())->first();

        if (!$client) {
            $client = new SMClient;
            $client->company_id = $this->scope->id();
        }

        $client->username = $username;
        $client->token = $token;
        $client->save();

        return $client;
    }

    public function getClient()
    {
        $client = SMClient::whereCompanyId($this->scope->id())->first();

        return $client;
    }

    /**
     * Get Orders
     * @param  array $filters Filters
     * @return QueryBuilder
     */
    public function getOrders($filters)
    {
        $orders = SMOrder::where('company_id', $this->scope->id())
            ->orderBy('id', 'desc');

        $this->applyOrdersFilters($orders, $filters);

        return $orders;
    }

    /************************ PRIVATE METHODS *******************/

    /**
     * Apply order filters
     * @param  queryBuilder $query QueryBuilder
     * @param  array $filters Filters
     * @return Void
     */
    private function applyOrdersFilters($query, $filters)
    {
        if (ine($filters, 'status')) {
            $query->whereStatus($filters['status']);
        }

        if(\Auth::user()->isSubContractorPrime()) {
            $query->whereIn('sm_orders.job_id', function($query) {
                $query->select('job_sub_contractor.job_id')
                    ->from('job_sub_contractor')
                    ->where('job_sub_contractor.sub_contractor_id', \Auth::id());
            });
        }
    }
}
