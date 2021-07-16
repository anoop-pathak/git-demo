<?php 

namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\HoverJob;
use Crypt;

class HoverJobRepository extends ScopedRepository
{
	/**
     * The base eloquent ActivityLog
     * @var Eloquent
     */
	protected $scope;
	protected $model;
 	function __construct(Context $scope, HoverJob $model)
	{
		$this->scope = $scope;
		$this->model = $model;
	}
 	/**
	* get hover job by job id
	*/
	public function getHoverJobByJobId($id)
	{
		return $this->model->whereJobId($id)->first();
	}

	/**
	* get hover job by job id
	*/
	public function getHoverJobByHoverJobId($id)
	{
		return $this->make()->whereHoverJobId($id)->firstOrFail();
	}

 	/**
	* get hover jobs
	*/
	public function getHoverJobs($filters = array())
	{
		$hoverJobs = $this->model->where('hover_jobs.company_id', getScopeId());

		$this->applyFilters($hoverJobs, $filters);

		return $hoverJobs;
	}

	/**
	* get hover job by ownerId or jobId
	*/
	public function getByJobIdAndOwnerId($jobId, $ownerId)
	{
		return $this->model->withTrashed()->whereJobId($jobId)->whereOwnerId($ownerId)->first();
	}
 	/**
	* delete jobs by job id
	*/
	public function deleteJobs($id, $ownerId)
	{
		$this->model->whereJobId($id)->where('owner_id', '!=' ,$ownerId)->delete();
	}

	public function applyFilters($query, $filters = array())
	{
		if(ine($filters, 'state')) {
			$query->where('state', $filters['state']);
		}

		if((ine($filters,'start_date') && ine($filters,'end_date'))
			&& ine($filters, 'date_range_type')) {
			$startDate = isSetNotEmpty($filters, 'start_date') ?: null;
			$endDate   = isSetNotEmpty($filters, 'end_date') ?: null;

			switch ($filters['date_range_type']) {
				case 'report_ordered_date':
				$query->reportOrderDate($startDate, $endDate);
				break;
			}
		}
	}
}