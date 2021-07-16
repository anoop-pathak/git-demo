<?php

namespace App\Models;

class Group extends BaseModel
{

    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'name' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }
}
