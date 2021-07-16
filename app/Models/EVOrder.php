<?php

namespace App\Models;

use Carbon\Carbon;

class EVOrder extends BaseModel
{

    protected $table = 'ev_orders';
    protected $fillable = [
        'company_id',
        'report_id',
        'product_type',
        'address',
        'delivery',
        'status_id',
        'sub_status_id',
        'claim_number',
        'job_id',
        'customer_id',
        'created_by',
        'meta'
    ];

    const ORDER_PRIMARY_STATUS = 1;

    protected $statusUpdateRule = [
        'StatusId' => 'required',
        'SubStatusId' => 'required',
        'ReportId' => 'required',
    ];

    protected function getStatusUpdateRules()
    {
        return $this->statusUpdateRule;
    }

    public function setAddressAttribute($value)
    {
        return $this->attributes['address'] = json_encode($value, true);
    }

    public function getAddressAttribute($value)
    {
        return json_decode($value);
    }

    public function setMetaAttribute($value) {
        return $this->attributes['meta'] = json_encode($value,true);
    }
    public function getMetaAttribute($value){
         return json_decode($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function status()
    {
        return $this->belongsTo(EVStatus::class, 'status_id', 'id');
    }

    public function subStatus()
    {
        return $this->belongsTo(EVSubStatus::class, 'sub_status_id', 'id');
    }

    // public function estimate() {
    // 	return $this->hasOne(Estimation::class,'ev_report_id','report_id');
    // }

    public function measurement()
    {
        return $this->hasOne(Measurement::class, 'ev_report_id', 'report_id');
    }

    public function pdfReport()
    {
        return $this->hasOne(EVReport::class, 'report_id', 'report_id')->where('file_mime_type', EVReport::PDF);
    }

    public function allReports()
    {
        return $this->hasMany(EVReport::class, 'report_id', 'report_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
