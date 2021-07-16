<?php

namespace App\Models;

class Timezone extends BaseModel
{

    protected $fillable = ['label', 'name', 'country_id'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'label' => 'required',
        'name' => 'required',
        'country_id' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
