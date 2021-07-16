<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EstimateChimney extends BaseModel
{
	use SoftDeletes;

    const ADDITION = 'addition';
	const SUBTRACTION = 'subtraction';

	protected $table = 'estimate_chimnies';

    protected $fillable = ['company_id', 'size', 'amount', 'arithmetic_operation'];

    protected $hidden = [];

    protected $dates = ['deleted_at'];

    protected $rules = [
		'size' => 'required',
		'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected $updateRules = [
		'size' => 'required',
		'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
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