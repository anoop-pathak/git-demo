<?php
namespace App\Repositories;

use App\Models\EstimateType;

Class EstimateTypesRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateType $model)
    {
		$this->model = $model;
	}

    public function getFilteredEstimateTypes($filters)
	{
		$estimateTypes = $this->model;
		$this->applyFilters($estimateTypes, $filters);

        return $estimateTypes;
	}

    public function getById($id, array $with = array())
	{
		$estimateType = $this->model->findOrFail($id);

        return $estimateType;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    }
}