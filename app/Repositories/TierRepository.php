<?php

namespace App\Repositories;

use App\Models\TierLibrary;
use App\Services\Contexts\Context;

class TierRepository extends ScopedRepository
{
    protected $scope;
    protected $model;

    public function __construct(TierLibrary $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    /**
     * create or update tier
     * @param  $input
     * @return $tier
     */
    public function createOrUpdate($input)
    {
        $tier = $this->model;

        if (ine($input, 'id')) {
            $tier = $tier->whereCompanyId($this->scope->id())
                ->whereId($input['id'])
                ->firstOrFail();
        }

        $tier->name = $input['name'];
        $tier->company_id = $this->scope->id();
        $tier->save();

        return $tier;
    }

    /**
     * get filtered tiers
     * @param  $input
     * @return $tiers
     */
    public function getFilteredTiers($input)
    {
        $tiers = $this->make();

        $this->applyFilters($tiers, $input);

        return $tiers;
    }

    /*************** Private Methods ***************/

    private function applyFilters($query, $filters = [])
    {
        if (ine($filters, 'name')) {
            $name = $filters['name'];
            $query->where('name', 'LIKE', "%$name%");
        }
    }
}
