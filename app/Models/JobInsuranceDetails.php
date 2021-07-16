<?php

namespace App\Models;

class JobInsuranceDetails extends BaseModel
{

    protected $table = 'job_insurance_details';

    public $fillable = [
        'insurance_company',
        'insurance_number',
        'phone',
        'fax',
        'email',
        'adjuster_name',
        'adjuster_phone',
        'adjuster_email',
        'rcv',
        'deductable_amount',
        'policy_number',
        'acv',
        'total',
        'adjuster_phone_ext',
        'depreciation',
        'supplement',
        'net_claim',
        'upgrade'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
