<?php

namespace App\Models;

class Greensky extends BaseModel {

    protected $table = 'greensky';

 	protected $fillable = ['job_id', 'application_id', 'status', 'company_id', 'customer_id'];

 	protected $rules = [
        'job_id'            => 'required',
        'application_id'    => 'required'
	];

    protected function getGreenskyRules()
    {
        return $this->rules;
    }

    public function setMetaAttribute($value)
    {
        $this->attributes['meta'] = json_encode($value);
    }

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}