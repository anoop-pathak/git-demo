<?php
namespace App\Models;

class JobPaymentLine extends BaseModel {

	protected $fillable = [
        'job_payment_id', 'jp_id', 'customer_id', 'line_type', 'company_id', 'quickbook_id', 'amount', 'origin', 'created_at', 'updated_at'
    ];
}