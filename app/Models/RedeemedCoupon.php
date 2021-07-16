<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class RedeemedCoupon extends Model
{

    // types
    const TRIAL = 'trial';
    const MONTHLY_FEE = 'monthly_fee';
    const SETUP_FEE = 'setup_fee';

    protected $fillable = [
        'company_id',
        'coupon_code',
        'coupon_detail',
        'start_date',
        'end_date',
        'is_active',
        'valid_for'
    ];

    protected $hidden = ['start_date', 'end_date'];

    public function getCouponDetailAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setCouponDetailAttribute($value)
    {
        $value = (array)$value;
        $this->attributes['coupon_detail'] = json_encode($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function coupon()
    {
        return $this->hasOne(DiscountCoupon::class, 'coupon_code', 'coupon_code');
    }

    public function scopeActive($query)
    {
        $query->whereIsActive(true);
    }

    public function scopeTrial($query)
    {
        $query->whereValidFor(self::TRIAL);
    }

    public function scopeMonthlyFee($query)
    {
        $query->whereValidFor(self::MONTHLY_FEE);
    }

    public function scopeSetupFee($query)
    {
        $query->whereValidFor(self::SETUP_FEE);
    }
}
