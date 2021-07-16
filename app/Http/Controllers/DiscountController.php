<?php

namespace App\Http\Controllers;

use App\Exceptions\DidNotActivatedException;
use App\Exceptions\InvalidCouponException;
use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\DiscountCoupon;
use App\Services\Discount\DiscountService;
use App\Transformers\CouponsTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class DiscountController extends ApiController
{

    protected $discountService;
    protected $response;

    function __construct(DiscountService $discountService, Larasponse $response)
    {
        $this->discountService = $discountService;
        $this->response = $response;
        parent::__construct();
    }

    public function list_discount_coupons()
    {
        $input = Request::onlyLegacy('product_id');

        $setupFeeCoupons = DiscountCoupon::validCoupons()
            ->where(function ($query) use ($input) {
                $query->whereNull('product_id');

                if ($input['product_id']) {
                    $query->orWhere('product_id', $input['product_id']);
                }
            })->whereType(DiscountCoupon::SETUP_FEE_COUPON)->get();

        $trialCoupons = DiscountCoupon::validCoupons()
            ->whereType(DiscountCoupon::TRIAL_COUPON)
            ->get();

        $monthlyFeeCoupons = DiscountCoupon::validCoupons()
            ->whereType(DiscountCoupon::MONTHLY_FEE_COUPON);

        if ($input['product_id']) {
            $monthlyFeeCoupons->whereProductId($input['product_id']);
        }

        $monthlyFeeCoupons = $monthlyFeeCoupons->get();

        $data['data']['subscription_fee_coupons'] = $this->response->collection($monthlyFeeCoupons, new CouponsTransformer)['data'];

        $data['data']['setup_fee_coupons'] = $this->response->collection($setupFeeCoupons, new CouponsTransformer)['data'];

        $data['data']['trial_coupon'] = $this->response->collection($trialCoupons, new CouponsTransformer)['data'];

        return ApiResponse::success($data);
    }

    public function apply_monthly_fee_coupon()
    {
        $input = Request::onlyLegacy('company_id', 'coupon');
        $validator = Validator::make($input, ['company_id' => 'required', 'coupon' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::find($input['company_id']);
        if (!$company) {
            return ApiResponse::errorNotFound(\Lang::get('response.error.invalid', ['attribute' => 'company Id']));
        }
        try {
            $this->discountService->redeemCoupon($company, $input['coupon']);
        } catch (DidNotActivatedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidCouponException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.coupon_redeemed')
        ]);
    }

    public function apply_setup_fee_coupon()
    {
        $input = Request::onlyLegacy('company_id', 'coupon');
        $validator = Validator::make($input, ['company_id' => 'required', 'coupon' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::find($input['company_id']);
        if (!$company) {
            return ApiResponse::errorNotFound(\Lang::get('response.error.invalid', ['attribute' => 'company Id']));
        }
        try {
            $this->discountService->adjustSetupFee($company, $input['coupon']);
        } catch (InvalidCouponException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.coupon_redeemed')
        ]);
    }

    public function applyTrial()
    {
        $input = Request::onlyLegacy('company_id', 'coupon');
        $validator = Validator::make($input, ['company_id' => 'required', 'coupon' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::find($input['company_id']);
        if (!$company) {
            return ApiResponse::errorNotFound(\Lang::get('response.error.invalid', ['attribute' => 'company Id']));
        }
        try {
            $this->discountService->applyTrial($company, $input['coupon']);
        } catch (InvalidCouponException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.coupon_redeemed')
        ]);
    }

    public function verify_coupon()
    {
        $input = Request::onlyLegacy('coupon', 'plan_code');
        $validator = Validator::make($input, DiscountCoupon::getCouponVerifyRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $coupon = $this->discountService->verifyCoupon($input['coupon'], $input['plan_code']);
            if (!$coupon) {
                return ApiResponse::errorGeneral('Invalid coupon');
            }
            return $this->response->item($coupon, new CouponsTransformer);
        } catch (\Recurly_NotFoundError $e) {
            return ApiResponse::errorGeneral(\Lang::get('response.error.invalid', ['attribute' => 'coupon']));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(\Lang::get('response.error.internal'), $e);
        }
    }
}
