<?php

namespace App\Models;

class SMOrder extends BaseModel
{

    // order statuses
    // const CREATED = 'new';
    const IN_PROGRESS = 'in_progress';
    const COMPLETED = 'completed';
    const CANCELLED = 'cancelled';
    const CS_HOLD = 'hold';
    const REFUNDED = 'refunded';
    const PARTIAL_REFUNDED = 'partial_refund';

    // order statuses codes
    // const CODE_IN_PROGRESS 	= 1; code status is not for inProgress
    const CODE_COMPLETED = 3;
    const CODE_CANCELLED = 4;
    const CODE_CS_HOLD = 22;
    const CODE_REFUNDED = 41;
    const CODE_PARTIAL_REFUNDED = 64;

    protected $table = 'sm_orders';

    protected $fillable = [
        'company_id',
        'order_id',
        'customer_id',
        'job_id',
        'details',
        'status',
        'created_by',
    ];

    protected $placeOrderRules = [
        'customer_id' => 'required',
        'job_id' => 'required',
        'Address' => 'required',
        'City' => 'required',
        'State' => 'required',
        'Zip' => 'required',
        'Latitude' => 'required',
        'Longitude' => 'required',
        'HomeOwnerName' => 'required',
        'Commercial' => 'required|boolean',
        'AdditionalDeliveryOptions' => 'in:Express3,Express6',
        'Special' => 'array',
        'DateOfLoss' => 'date',
        'Comments' => 'max:400',
        'Commercial' => 'required|boolean',
    ];

    protected function getPlaceOrderRules()
    {

        return $this->placeOrderRules;
    }

    public function getDetailsAttribute($value)
    {
        return json_decode($value);
    }

    public function setDetailsAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['details'] = json_encode($value);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function estimate()
    {
        return $this->hasOne(Estimation::class, 'sm_order_id', 'order_id');
    }

    public function measurement()
    {
        return $this->hasOne(Measurement::class, 'sm_order_id', 'order_id');
    }


    public function reportsFiles()
    {
        return $this->hasMany(SMReportFile::class, 'order_id', 'order_id');
    }
}
