<?php
namespace App\Repositories;

use App\Models\QBDesktopProductModel;
use App\Services\Contexts\Context;

Class QBDesktopProductRepository extends ScopedRepository
{

	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
	protected $address;
	protected $jobWorkflowHistory;
	protected $jobNumber;
    protected $scope;

	public function __construct(QBDesktopProductModel $model, Context $scope) {
		$this->model = $model;
		$this->scope = $scope;
    }

	/**
	 * Get Project By Id
	 * @param  [type] $id   [description]
	 * @param  array  $with [description]
	 * @return [type]       [description]
	 */
	public function getProjectById($id, array $with = array())
	{
	}

    public function getFilteredProducts($filters, $sortable = true)
	{
		$model = $this->make();

        if($sortable) {
			$model->sortable();
        }

		return $model;
	}
}