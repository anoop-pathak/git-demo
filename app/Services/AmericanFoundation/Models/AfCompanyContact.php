<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\Company;

class AfCompanyContact extends BaseModel
{

    protected $table = "af_company_contacts";

    protected $fillable = [
        'company_id', 'group_id', 'company_contact_id',
        'af_id', 'af_owner_id', 'account_id', 'salutation', 'first_name', 'last_name',
        'email', 'title', 'phone', 'fax', 'mobile_phone', 'other_address', 'other_street',
        'other_city', 'other_state','other_postal_code', 'other_country', 'other_latitude',
        'other_longitude', 'mailing_address', 'mailing_street', 'mailing_city',
        'mailing_state', 'mailing_postal_code', 'mailing_country', 'mailing_latitude',
        'mailing_longitude', 'description', 'options', 'csv_filename', 'created_by', 'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}