<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimateChimney;

Class EstimateChimniesRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateChimney $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
    }

	public function getChimnies($filters)
	{
		$includeData = $this->includeData($filters);
		$estimateChimnies = $this->make($includeData);

		$this->applyFilters($estimateChimnies, $filters);

        return $estimateChimnies;
	}

    public function save($size, $amount)
	{
		$chimney = EstimateChimney::firstOrNew([
			'company_id' => getScopeId(),
			'size' => $size
		]);
		$chimney->amount = $amount;
		$chimney->arithmetic_operation = EstimateChimney::ADDITION;
		$chimney->save();

        return $chimney;
	}

    public function update($chimney, $size, $amount)
	{
		$chimney->size = $size;
		$chimney->amount = $amount;
		$chimney->save();

        return $chimney;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    }

    private function includeData($input)
	{
		$with = [];
        if(!isset($input['includes'])) return $with;
		$includes = (array)$input['includes'];

        return $with;
	}
}