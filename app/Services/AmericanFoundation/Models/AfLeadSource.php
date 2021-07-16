<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Models\Company;
use App\Services\AmericanFoundation\Models\AfReferral;

class AfLeadSource extends BaseModel
{

    protected $table = "af_lead_sources";

    protected $fillable = [
        'company_id', 'group_id', 'af_id', 'owner_id', 'name', 'comments',
        'components', 'prospect_id', 'prospect', 'marketing_source_id',
        'prospect_email', 'options', 'created_by', 'updated_by', 'csv_filename',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function afReferral()
    {
        return $this->belongsTo(AfReferral::class, 'marketing_source_id', 'af_id');
    }
}