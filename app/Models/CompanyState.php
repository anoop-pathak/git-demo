<?php

namespace App\Models;

class CompanyState extends BaseModel
{
    protected $fillable = ['state_id', 'tax_rate'];

    protected $table = 'company_state';

    public $timestamps = false;

    protected $rules = [
        'state_id' => 'required',
        // 'tax_rate' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
