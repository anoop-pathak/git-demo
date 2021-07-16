<?php
namespace App\Models;

class EstimateGutter extends BaseModel
{
	protected $fillable = ['company_id', 'size', 'amount', 'protection_amount'];

    protected $hidden = [];

    protected $rules = [
		'size' => 'required|in:linear_ft',
		'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
		'protection_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected $updateRules = [
		'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
		'protection_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected function getRules()
	{
		return $this->rules;
	}

    protected function getUpdateRules()
	{
		return $this->updateRules;
	}
}