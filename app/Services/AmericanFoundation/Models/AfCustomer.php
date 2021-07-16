<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\Company;
use App\Models\Customer;
use App\Services\AmericanFoundation\Models\AfLeadSource;
use App\Services\AmericanFoundation\Models\AfUser;

class AfCustomer extends BaseModel
{

    protected $table = "af_customers";

    protected $fillable = [
        'af_id', 'company_id', 'group_id', 'customer_id', 'rep_id', 'referred_by_type',
        'company_name', 'first_name', 'last_name', 'email', 'secondary_first_name',
        'secondary_last_name', 'billing_address', 'billing_city', 'billing_state',
        'billing_zip', 'customer_address', 'customer_city', 'customer_state',
        'customer_zip', 'management_company', 'property_name', 'origin',
        'note', 'options', 'csv_filename'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function jpCustomer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function afLeadSource()
    {
        return $this->hasOne(AfLeadSource::class, 'prospect_id', 'af_id');
    }

    public function afCustomerRep()
    {
        return $this->hasOne(AfUser::class, 'af_id', 'rep_id');
    }
}