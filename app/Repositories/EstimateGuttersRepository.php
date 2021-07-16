<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimateGutter;

Class EstimateGuttersRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateGutter $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
    }

	public function getGutters($filters)
	{
		$includeData = $this->includeData($filters);
		$estimateGutters = $this->make($includeData);

		$this->applyFilters($estimateGutters, $filters);
		return $estimateGutters;
	}

    public function save($size, $amount, $protectionAmount)
	{
		$gutters = EstimateGutter::firstOrNew([
			'company_id' => getScopeId()
		]);
		$gutters->size = $size;
		$gutters->amount = $amount;
		$gutters->protection_amount = $protectionAmount;
		$gutters->save();

        return $gutters;
	}

    public function update($estimateGutter, $amount, $protectionAmount)
	{
		$estimateGutter->amount = $amount;
		$estimateGutter->protection_amount = $protectionAmount;
		$estimateGutter->save();

        return $estimateGutter;
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