<?php
namespace App\Repositories;

use App\Models\Greensky;
use App\Services\Contexts\Context;

class GreenskyRepository extends ScopedRepository {

	/**
     * The base eloquent Greensky
     * @var Eloquent
     */
	protected $model;

	public function __construct(Greensky $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
	}

	/**
	 * @param  $jobId
	 * @param  $input
	 * 
	 * @return $greenskyApp
	 */
	public function saveOrUpdate($job, $input)
	{
		$greenskyApp = Greensky::firstOrNew([
			'job_id'		 => $job->id,
			'company_id'	 => $job->company_id,
			'application_id' => $input['application_id'],
			'customer_id'	 => $job->customer->id,
		]);

		if(isset($input['status'])) {
			$greenskyApp->status = $input['status'];
		}
		if(isset($input['meta'])) {
			$greenskyApp->meta = $input['meta'];
		}

		$greenskyApp->save();

		return $greenskyApp;
	}


	/**
	 * get listing of greensky apps
	 * @param  Integer | $jobId | Id of a Job
	 * @return query
	 */
	public function getListing($jobId)
	{
		$greensky = $this->model->where('job_id', $jobId);

		return $greensky;
	}
}