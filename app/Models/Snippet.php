<?php

namespace App\Models;

class Snippet extends BaseModel
{

    protected $fillable = ['title', 'description'];

    protected $hidden = ['company_id'];

    protected $rules = [

        'title' => 'required',
        'description' => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
