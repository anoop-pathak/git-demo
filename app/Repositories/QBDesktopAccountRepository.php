<?php
namespace App\Repositories;

use App\Models\QBDesktopAccountModel;
use App\Services\Contexts\Context;

Class QBDesktopAccountRepository extends ScopedRepository
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

	public function __construct(QBDesktopAccountModel $model, Context $scope) {
		$this->model = $model;
		$this->scope = $scope;
    }

	public function getFilteredAccounts($filters, $sortable = true)
	{
		$model = $this->make();
		if($sortable) {
			$model->sortable();
		}
		return $model;
	}
}