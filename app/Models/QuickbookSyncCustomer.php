<?php
namespace App\Models;

class QuickbookSyncCustomer extends BaseModel
{
	protected $table = 'quickbook_sync_customers';

	protected $fillable = [
		'company_id', 'created_by', 'last_modified_by', 'mapped', 'retain_financial', 'queue_executed_count'
	];

	const ORIGIN_JP = 'jp';
    const ORIGIN_QB = 'qb';
    const MATCHING_CUSTOMERS = 'matching_customers';
    const ACTION_REQUIRED = 'action_required';

    const READY_FOR_SYNCHING   	= 'ready_for_synching';
    const SUBMIT_FOR_SYNCHING  	= 'submit_for_synching';
    const SYNCHING 				= 'synching';
    const SYNC_COMPLETE     	= 'synch_complete';
    const SYNC_FAILED       	= 'synch_failed';

    const RETAIN_QB_FINANCIAL = 0;
    const RETAIN_JP_FINANCIAL = 1;
    const RETAIN_JP_AND_QB_FINANCIAL = 2;

	public function getMetaAttribute($value)
	{
		return json_decode($value, true);
	}

	public function getErrorsAttribute($value)
	{
		return json_decode($value, true);
	}

	public function getMsgAttribute($value)
	{
		return json_decode($value, true);
	}

	public function getPhonesAttribute($value)
	{
		return json_decode($value, true);
	}

	public function getAdditionalEmailsAttribute($value)
	{
		return json_decode($value, true);
	}

	/***** Protected Section *****/

	protected function markCustomersAsDifferentRules()
	{
		$rules = [
			'details' => 'required|array'
		];

		$details = \Request::get('details');

		foreach ((array)$details as $key => $details) {
			$rules["details.{$key}.sync_qb_id"] = 'required';
			$rules["details.{$key}.sync_customer_id"] = 'required';
		}

		return $rules;
	}

	protected function ignoreOrReinstateRules()
	{
		$rules = [
			'details' => 'required|array',
			'type' => 'required|in:qb,jp,matching_customers,action_required'
		];

		$details = \Request::get('details');

		foreach ((array)$details as $key => $details) {
			$rules["details.{$key}.sync_qb_id"] = 'required_if:type,==,matching_customers';
			$rules["details.{$key}.sync_customer_id"] = 'required';
		}

		return $rules;
	}

	protected function getSelectFinancialRules()
	{
		$rules = [
			'type' => 'required|in:0,1,2',
			'details' => 'required|array'
		];

		$details = \Request::get('details');

		foreach ((array)$details as $key => $details) {
			$rules["details.{$key}.sync_qb_id"] = 'required';
			$rules["details.{$key}.sync_customer_id"] = 'required';
		}

		return $rules;
	}

	protected function getJpOrQbCustomerSyncRules(){
		$rules = [
			'customer_ids' => 'required'
		];
		return $rules;
	}

	protected function getMatchingCustomerSyncRules()
	{
		$rules = [
			'details' => 'required|array'
		];

		$details = \Request::get('details');

		foreach ((array)$details as $key => $details) {
			$rules["details.{$key}.sync_qb_id"] = 'required';
			$rules["details.{$key}.sync_customer_id"] = 'required';
		}

		return $rules;
	}
}