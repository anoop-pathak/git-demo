<?php
namespace App\Models;

use Carbon\Carbon;

class FinancialAccountDetail extends BaseModel {
	protected $fillable = ['type', 'name'];

    protected $table = 'account_deatil_types';

	protected $rules = [
		'name'	=> 'required',
		'type'	=> 'required'
	];

	protected function getRules() {
        return $this->rules;
    }


    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}