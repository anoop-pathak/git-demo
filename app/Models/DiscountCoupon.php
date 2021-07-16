<?php

namespace App\Models;
use Carbon\Carbon;

class DiscountCoupon extends BaseModel
{

    protected $fillable = [
        'is_active',
        'coupon_code',
        'name',
        'discount_type',
        'discount_percent',
        'discount_amount',
        'single_use',
        'cycles',
        'duration',
        'temporal_unit',
        'temporal_amount',
        'product_id',
        'plan_code',
        'type',
        'is_offer',
        'start_date_time',
        'end_date_time'
    ];

    // discount type..
    const PERCENT = 'percent';
    const DOLLARS = 'dollars';

    // coupon type
    const MONTHLY_FEE_COUPON = 'monthly_fee_coupon';
    const SETUP_FEE_COUPON = 'setup_fee_coupon';
    const TRIAL_COUPON = 'trial_coupon';

    protected $couponVerifyRules = [
        'coupon' => 'required',
        // 'type' 	 	 => 'required|in:setup_fee_coupon,monthly_fee_coupon,trial',
        'plan_code' => 'required',
    ];

    protected function getCouponVerifyRules()
    {
        return $this->couponVerifyRules;
    }

    public function scopeOffers($query)
    {
        $query->whereIsOffer(true);
    }

    public function scopeActive($query, $status = true)
    {
        $query->whereIsActive($status);
    }

    public function scopeValidCoupons($query)
    {
        $query->active();

        $query->where(function ($query) {

            $currentDateTime = Carbon::now('US/Eastern')->toDateTimeString();
            $query->where('start_date_time', '<=', $currentDateTime);
            $query->where('end_date_time', '>=', $currentDateTime);

            $query->orWhere(function ($query) {
                $query->whereNull('start_date_time');
                $query->orWhereNull('end_date_time');
            });
        });
    }
}
