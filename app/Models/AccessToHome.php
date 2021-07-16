<?php
namespace App\Models;

class AccessToHome extends BaseModel
{
    protected $table = 'access_to_home';

    protected $fillable = ['company_id', 'type', 'amount'];

	protected $rules = [
		'type' => 'required|in:restricted,open',
		'amount' => 'required',
	];

    protected $updateRules = [
		'amount' => 'required',
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