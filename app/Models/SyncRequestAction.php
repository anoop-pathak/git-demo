<?php
namespace App\Models;

class SyncRequestAction extends BaseModel
{
	protected $fillable = [
		'company_id', 'batch_id', 'action_type', 'created_by'
	];

	const JP_TO_QB = 'jp_to_qb';
	const QB_TO_JP = 'qb_to_jp';
	const MATCHING_CUSTOMERS = 'matching_customers';
	const ACTION_REQUIRED = 'action_required';
}