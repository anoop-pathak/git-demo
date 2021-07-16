<?php

namespace App\Models;

class Department extends BaseModel
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

    public function users()
    {
        return $this->belongsToMany(Department::class, 'user_department', 'department_id', 'user_id');
    }
}
