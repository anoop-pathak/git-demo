<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;

class ProductsController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /products
     *
     * @return Response
     */

    public function get_public_products()
    {
        $input = Request::onlyLegacy('web');

        $products = Product::active()
            ->public()
            ->with([
                'offers' => function ($query) {
                    $query->select('product_id', 'name', 'coupon_code', 'type', 'start_date_time', 'end_date_time');
                }
            ])->orderBy('order', 'asc');

        // if(!$input['web']) {
        // 	$products->whereId(Product::PRODUCT_GAF_PLUS);
        // }

        $products = $products->get();
        foreach ($products as $key => $product) {
            $products[$key]['subscription_plan'] = $product->subscriptionPlans()->first();
        }

        return ApiResponse::success([
            'data' => $products,
            'meta' => [
                'partner_plans_available' => config('jp.partner_plans.available'),
            ],
        ]);
    }

    public function get_all_products()
    {

        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }
        $products = Product::active()->get();
        foreach ($products as $key => $product) {
            $products[$key]['subscription_plan'] = $product->subscriptionPlans()->first();
        }

        return ApiResponse::success(['data' => $products]);
    }

    /**
     * Get Partner Plan With Code
     * Get /get_partner_plan
     *
     * @return Response
     */
    public function getPartnerPlans()
    {
        $input = Request::onlyLegacy('code');

        $validator = Validator::make($input, ['code' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $partnerPlans = config('jp.partner_plans.codes');
        $code = $input['code'];

        if (!isset($partnerPlans[$code])) {
            INVALID_CODE:
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'code']));
        }

        $product = Product::active()->whereId($partnerPlans[$code])->first();

        if (!$product) {
            goto INVALID_CODE;
        }

        $product['subscription_plan'] = $product->subscriptionPlans()->first();

        return ApiResponse::success(['data' => $product]);
    }
}
