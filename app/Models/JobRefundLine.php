<?php
namespace App\Models;

use Carbon\Carbon;

class JobRefundLine extends BaseModel
{
    protected $fillable = ['job_refund_id', 'financial_product_id', 'quantity', 'rate', 'description', 'trade_id', 'work_type_id', 'quickbook_id', 'is_taxable'];

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function refund()
    {
        return $this->belongsTo(JobRefund::class);
    }

    public function financialProduct()
    {
        return $this->belongsTo(FinancialProduct::class);
    }

    public function workType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
