<?php

namespace App\Models;

class Product extends BaseModel
{
    protected $fillable = ['title', 'public', 'active', 'order'];

    const PRODUCT_JOBPROGRESS = 1;
    const PRODUCT_JOBPROGRESS_PLUS = 2;
    const PRODUCT_JOBPROGRESS_PRO = 3;
    const PRODUCT_JOBPROGRESS_PLUS_FREE = 4;
    const PRODUCT_JOBPROGRESS_BASIC_FREE = 5;
    const PRODUCT_JOBPROGRESS_PRO_FREE = 6;
    const PRODUCT_GAF_PLUS = 7;
    const PRODUCT_JOBPROGRESS_STANDARD = 8;
    const PRODUCT_JOBPROGRESS_PARTNER = 9;
    const PRODUCT_JOBPROGRESS_MULTI = 10;
    const PRODUCT_JOBPROGRESS_25 = 11;

    protected $hidden = ['created_at', 'updated_at', 'public', 'active', 'order'];

    public function subscriptionPlans()
    {
        return $this->hasMany(SubscriptionPlan::class);
    }

    public function scopePublic($query)
    {
        return $query->wherePublic(true);
    }

    public function scopeActive($query)
    {
        return $query->whereActive(true);
    }

    public function offers()
    {
        return $this->hasMany(DiscountCoupon::class)->offers()->validCoupons();
    }
}
