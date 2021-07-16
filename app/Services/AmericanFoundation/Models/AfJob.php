<?php

namespace App\Services\AmericanFoundation\Models;

use App\Models\BaseModel;
use App\Services\AmericanFoundation\Models\AfCustomer;

class AfJob extends BaseModel
{

    protected $table = "af_jobs";

    protected $fillable = [
        'company_id', 'group_id', 'af_id', 'owner_id', 'name', 'job_number',
        'comments', 'job_type', 'project_id', 'project_manager_id',
        'project_number', 'af_customer_af_id', 'status', 'total_cost',
        'receipts_adjustment_total', 'total_sale', 'summary_total_with_tax',
        'total_with_tax', 'options', 'created_by', 'updated_by', 'csv_filename'
    ];

    public function customer()
    {
        return $this->belongsTo(AfCustomer::class, 'af_customer_af_id', 'af_id');
    }
}