<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class EstimatePitch extends BaseModel
{
	use SoftDeletes;

    protected $table = 'estimate_pitch';

    protected $fillable = ['company_id', 'name', 'fixed_amount'];

    protected $hidden = [];

    protected $dates = ['deleted_at'];

    protected $updateRules = [
		'name' => 'required',
		'fixed_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
	];

    protected $rules = [
		'name' => 'required',
		'fixed_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/'
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