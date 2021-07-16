<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\DripCampaign;

Class DripCampaignRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
	protected $model;
	protected $scope;

	function __construct(DripCampaign $model, Context $scope)
	{
		$this->scope = $scope;
		$this->model = $model;
	}


	/**
	* @ Save Drip Campaign
	*/
	public function createDripCampaign($dripCampaignData)
	{
		$dripCampaignData['company_id'] = $this->scope->id();
		$dripCampaignData['status'] = DripCampaign::STATUS_READY;
		$dripCampaign = $this->model->create($dripCampaignData);

		return $dripCampaign;
	}
	/**
	* @ Get Drip Campaign
	*/
	public function getFilteredCampaigns($filters = array())
	{
		$with = $this->includeData($filters);

		$dripCampaign = $this->make($with);
		$dripCampaign->sortable();
		if(!ine($filters, 'sort_by')) {
			$dripCampaign->orderBy('created_at', 'desc');
		}

		$this->applyFilters($dripCampaign, $filters);

		return $dripCampaign;
	}

	public function applyFilters($query, $filters)
	{
		if(ine($filters, 'job_id')) {
			$query->where('job_id', $filters['job_id']);
		}

		if(ine($filters, 'customer_id')) {
			$query->where('customer_id', $filters['customer_id']);
		}

		if(ine($filters, 'status')) {
			$query->where('status', $filters['status']);
		}

		if(ine($filters, 'name')) {
			$query->where('name', $filters['name']);
		}

		if(ine($filters,'created_date')) {
			$date = $filters['created_date'];
			$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('drip_campaigns.created_at').", '%Y-%m-%d') = '$date'");
		}

		if(!ine($filters, 'include_canceled_campaign')) {
			$query->whereNull('canceled_date_time');
		}

	}

	public function getCampaignById($id)
	{
		return $this->make()->where('canceled_date_time', null)->findOrFail($id);
	}

	/**
	 * includeData
	 * @param  Array $input | Input Array
	 * @return Array
	 */
	private function includeData($input = [])
	{
		$with = [];

		$includes = isset($input['includes']) ? $input['includes'] : [];
		if(!is_array($includes) || empty($includes)) return $with;

		if(in_array('customer', $includes)) {
			$with[] = 'customer';
		}

		if(in_array('job', $includes)) {
			$with[] = 'job';
			$with[] = 'job.address';
			$with[] = 'job.address.state';
			$with[] = 'job.address.country';
			$with[] = 'job.jobMeta';
			$with[] = 'job.jobWorkflow.stage';
			$with[] = 'job.customer.phones';
			$with[] = 'job.workTypes';
		}

		if (in_array('drip_campaign_schedulers', $includes)) {
			$with[] = 'schedulers';
			$with[] = 'schedulers.emailDetail';
		}

		if (in_array('email', $includes)) {
			$with[] = 'email';
		}
		if (in_array('email.recipients', $includes)) {
			$with[] = 'email.recipients';
		}
		if (in_array('email.attachments', $includes)) {
			$with[] = 'email.attachments';
		}

		return $with;
	}
}