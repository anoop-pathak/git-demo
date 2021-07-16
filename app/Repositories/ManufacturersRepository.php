<?php
namespace App\Repositories;

use App\Models\Manufacturer;

Class ManufacturersRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Manufacturer $model)
    {
		$this->model = $model;
	}

    public function getFilteredManufacturers($filters)
	{
		$manufacturers = $this->model;
		$this->applyFilters($manufacturers, $filters);

        return $manufacturers;
	}

    public function getById($id, array $with = array())
	{
		$manufacturer = $this->model->findOrFail($id);

		return $manufacturer;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    }
}