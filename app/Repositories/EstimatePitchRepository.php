<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimatePitch;

Class EstimatePitchRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimatePitch $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getFilteredPitch($filters = [])
	{
		$with = [];
		$estimatePitch = $this->make($with);
		$this->applyFilters($estimatePitch, $filters);

        return $estimatePitch;
	}

    public function save($name, $fixedAmount)
	{
		$estimatePitch = new EstimatePitch;
 		$estimatePitch->company_id = getScopeId();
 		$estimatePitch->name = $name;
 		$estimatePitch->fixed_amount = $fixedAmount;
 		$estimatePitch->save();

         return $estimatePitch;
	}

    public function update($estimatePitch, $fixedAmount, $name)
	{
		$estimatePitch->fixed_amount = $fixedAmount;
		$estimatePitch->name = $name;
		$estimatePitch->save();

        return $estimatePitch;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
		if(ine($filters, 'ids')) {
			$query->whereIn('estimate_pitch.id', (array) $filters['ids']);
		}
    }
}