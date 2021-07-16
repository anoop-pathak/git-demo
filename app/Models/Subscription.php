<?php

namespace App\Models;

class Subscription extends BaseModel
{

    protected $fillable = [
        'company_id',
        'product_id',
        'activated_by',
        'modified_by',
        'activation_date',
        'subscription_plan_id',
        'status',
        'redeemed_monthly_fee_coupon',
        'remaining_cycles',
        'coupon_valid_till',
        'next_renewal_date',
        'next_renewal_plan',
        'current_cycle_started_at',
        'gaf_code',
        'current_plan_code'
    ];

    //subscription status
    const INACTIVE = 'inactive'; // before activation
    const ACTIVE = 'active'; // Activation By Super-admin
    const MANUALLY_SUSPENDED = 'manually_suspended'; // suspended by super-admin
    const SUSPENDED = 'suspended'; // suspended by system due to payment failed
    const TERMINATED = 'terminated'; // terminated by super-admin
    const UNSUBSCRIBED = 'unsubscribed'; // subscriber unsubscribed
    const TRIAL = 'trial';

    const SETUP_FEE = 'setup_fee';

    protected static $activationRules = [
        'company_id' => 'required'
    ];

    protected static $billingRules = [
        'company_id' => 'required',
        'token' => 'required',
        'address' => 'required',
        'city' => 'required',
        'state_id' => 'required',
        'country_id' => 'required',
        'zip' => 'required',
        'email' => 'required|email',
    ];

    protected static $updateBillingRules = [
        'company_id' => 'required',
        'address' => 'required',
        'city' => 'required',
        'state_id' => 'required',
        'country_id' => 'required',
        'zip' => 'required',
    ];

    // subscription suspend, reactive, terminate, unsubscribed.
    protected static $subscriptionRules = [
        'company_id' => 'required',
        'password' => 'required'
    ];

    public static function getActivationRules()
    {
        return self::$activationRules;
    }

    public static function getBillingRules()
    {
        return self::$billingRules;
    }

    public static function getUpdateBillingRules()
    {
        return self::$updateBillingRules;
    }

    public static function getSubscriptionRules()
    {
        return self::$subscriptionRules;
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
