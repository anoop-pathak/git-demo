<?php

namespace App\Services\Discount;

use App\Exceptions\InvalidCouponException;
use App\Models\Company;
use App\Models\DiscountCoupon;
use App\Models\RedeemedCoupon;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Contexts\Context;
use App\Services\Recurly\Recurly;
use App\Services\Subscriptions\SubscriptionServices;
use App\Transformers\CouponsTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class DiscountService
{

    protected $recurly;
    protected $scope;

    function __construct(Recurly $recurly, Context $scope)
    {
        $this->recurly = $recurly;
        $this->scope = $scope;
    }

    /**
     * Redeem discount coupon
     * @param object | instance of Company Model
     * @param string | recurly discount coupon
     * @return bool
     */
    public function redeemCoupon(Company $company, $couponCode)
    {
        $coupon = $this->getCoupon($couponCode);
        if (is_null($coupon) || ($coupon->type != DiscountCoupon::MONTHLY_FEE_COUPON)) {
            throw new InvalidCouponException(\Lang::get('response.error.invalid', ['attribute' => 'Coupon']));
        }

        $subscription = $company->subscription;

        if (!$subscription) {
            throw new \Exception(\Lang::get('response.error.internal'));
        }

        $plan = SubscriptionPlan::whereCode($coupon->plan_code)->whereCycles($coupon->cycles)->first();

        if ($subscription->product_id != $plan->product_id) {
            throw new InvalidCouponException(\Lang::get('response.error.invalid', ['attribute' => 'Coupon']));
        }
        $subscription->subscription_plan_id = $plan->id;
        if (is_null($subscription->recurly_uuid)) {
            $subscription->current_plan_code = $plan->code;
        }
        // if(!is_null($subscription->recurly_uuid) && ($subscription->remaining_cycles != 'unlimited')) {
        //   $subscription->remaining_cycles = (int)$subscription->remaining_cycles + (int)$plan->cycles;
        // }else {
        $subscription->remaining_cycles = $plan->cycles;
        // }
        $subscription->redeemed_monthly_fee_coupon = $coupon->coupon_code;
        if (!is_null($subscription->recurly_uuid) && !is_null($subscription->next_renewal_date)) {
            $subscription->next_renewal_plan = $plan->code;
        }

        $subscription->save();

        $this->saveRedeemedCoupon($company, $coupon, RedeemedCoupon::MONTHLY_FEE);

        if (!is_null($subscription->recurly_uuid)) {
            App::make(SubscriptionServices::class)->checkForNextUpdation($subscription->company);
        }
        return true;
    }

    public function adjustSetupFee(Company $company, $couponCode)
    {
        $coupon = $this->getCoupon($couponCode);
        if (is_null($coupon) || ($coupon->type != DiscountCoupon::SETUP_FEE_COUPON)) {
            throw new InvalidCouponException(\Lang::get('response.error.invalid', ['attribute' => 'Coupon']));
        }

        $subscription = $company->subscription;

        if (!empty($coupon->product_id) && ($coupon->product_id != $subscription->product_id)) {
            throw new InvalidCouponException(\Lang::get('response.error.invalid', ['attribute' => 'Coupon']));
        }

        if (!$subscription) {
            throw new \Exception(\Lang::get('response.error.internal'));
        }

        $applicablePlan = $subscription->plan;

        $setup_fee = 0;
        if ($applicablePlan->setup_fee) {
            $setup_fee = $applicablePlan->setup_fee;
        }

        if ($coupon->discount_type == DiscountCoupon::PERCENT) {
            $subscription->setup_fee = ($coupon->discount_percent / 100) * $setup_fee;
        } else {
            $subscription->setup_fee = $coupon->discount_amount;
        }
        $subscription->save();
        $this->saveRedeemedCoupon($company, $coupon, RedeemedCoupon::SETUP_FEE);
        return true;
    }

    public function verifyCoupon($couponCode, $planCode)
    {
        $coupon = $this->getCoupon($couponCode);

        if (is_null($coupon)) {
            return false;
        }

        if ($coupon->type == DiscountCoupon::TRIAL_COUPON) {
            return $coupon;
        }

        if (!$coupon->product_id) {
            return $coupon;
        }

        $plan = SubscriptionPlan::whereCode($planCode)->first();
        if ($plan->product_id != $coupon->product_id) {
            return false;
        }

        return $coupon;
    }

    public function applyTrial($company, $couponCode)
    {
        $coupon = $this->getCoupon($couponCode);

        if (is_null($coupon) || ($coupon->type != DiscountCoupon::TRIAL_COUPON)) {
            throw new InvalidCouponException(trans('response.error.invalid', ['attribute' => 'Trial Coupon']));
        }

        $subscription = $company->subscription;

        if (!$subscription) {
            throw new \Exception(trans('response.error.internal'));
        }

        if ($subscription->status == Subscription::ACTIVE) {
            throw new \Exception("Trial not possible after subscription activation");
        }

        $this->saveRedeemedCoupon($company, $coupon, RedeemedCoupon::TRIAL);
    }

    /******************* Private Section **********************/

    /**
     * Save redeemed Coupon
     * @param  Subscription $subscription | Subscription instance
     * @param  Coupon $coupon | Coupon Data
     * @param  String $validFor | For e.g, setup_fee or monthly_fee
     * @return void
     */
    private function saveRedeemedCoupon(Company $company, $coupon, $validFor)
    {

        // deactivate previous coupon..
        RedeemedCoupon::where('company_id', $company->id)
            ->where('valid_for', $validFor)
            ->update(['is_active' => false]);

        $transform = App::make('Sorskod\Larasponse\Larasponse');
        $couponDetail = $transform->item($coupon, new CouponsTransformer);
        $redeemed = RedeemedCoupon::create([
            'company_id' => $company->id,
            'coupon_code' => $coupon->coupon_code,
            'coupon_detail' => $couponDetail,
            'valid_for' => $validFor,
            'is_active' => true,
        ]);
        $subscription = $company->subscription;
        if (!is_null($subscription->recurly_uuid) && !is_null($subscription->next_renewal_date)) {
            $redeemed->start_date = $subscription->next_renewal_date;
            if ($coupon->temporal_unit == 'week') {
                $redeemed->end_date = (new Carbon($subscription->next_renewal_date))->addWeeks($subscription->remaining_cycles)->toDateTimeString();
            } else {
                $redeemed->end_date = (new Carbon($subscription->next_renewal_date))->addMonths($subscription->remaining_cycles)->toDateTimeString();
            }
        }
        // else {
        //   $redeemed->start_date = Carbon::now();
        //   $redeemed->end_date = !is_null($coupon->cycles) ? Carbon::now()->addMonths($coupon->cycles)->toDateTimeString() : Carbon::now();
        // }
        $redeemed->save();
    }

    private function getCoupon($code)
    {
        $coupon = DiscountCoupon::validCoupons()
            ->where('coupon_code', $code)
            ->first();

        return $coupon;
    }
}
