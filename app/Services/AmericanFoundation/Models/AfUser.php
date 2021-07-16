<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\State;
use App\Models\User;
use App\Models\Country;
use App\Models\Company;

class AfUser extends BaseModel
{
    protected $table = "af_users";

    protected $fillable = [
        'company_id', 'group_id', 'af_id', 'username', 'first_name', 'last_name', 'email',
        'company_name', 'street', 'city', 'state', 'postal_code', 'country', 'phone',
        'fax', 'mobile_phone', 'is_active', 'about_me', 'options', 'csv_filename'
    ];

    public function state_info()
    {
        return $this->belongsTo(State::class, 'state', 'code');
    }

    public function country_info()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function jpUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}