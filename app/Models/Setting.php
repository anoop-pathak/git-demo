<?php

namespace App\Models;

class Setting extends BaseModel
{
    protected $fillable = ['company_id', 'user_id', 'key', 'name', 'value'];
    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'key' => 'required',
        'name' => 'required',
        'value' => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function getValueAttribute($value)
    {
        if (is_array(json_decode($value, true))) {
            return json_decode($value, true);
        } else {
            return $value;
        }
    }

    public function setValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
