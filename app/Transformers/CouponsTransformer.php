<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class CouponsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($coupon)
    {
        return [
            'code' => $coupon->coupon_code,
            'name' => $coupon->name,
            'type' => $coupon->type,
            'discount_type' => $coupon->discount_type,
            'discount_percent' => $coupon->discount_percent,
            'discount_amount' => $this->getAmount($coupon),
            'single_use' => isset($coupon->single_use) ? (bool)$coupon->single_use : null,
            'cycles' => isset($coupon->cycles) ? $coupon->cycles : null,
            'applies_to_all_plans' => isset($coupon->applies_to_all_plans) ? $coupon->applies_to_all_plans : null,
            'duration' => isset($coupon->duration) ? $coupon->duration : null,
            'temporal_unit' => isset($coupon->temporal_unit) ? $coupon->temporal_unit : null,
            'temporal_amount' => isset($coupon->temporal_amount) ? $coupon->temporal_amount : null,
        ];
    }

    /*************************** Private Function *****************************/

    private function getAmount($coupon)
    {
        if (isset($coupon->discount_in_cents->USD->amount_in_cents) && !empty(isset($coupon->discount_in_cents->USD->amount_in_cents))) {
            return $coupon->discount_in_cents->USD->amount_in_cents / 100;
        } elseif (isset($coupon->discount_amount) && !empty($coupon->discount_amount)) {
            return $coupon->discount_amount;
        } else {
            return null;
        }
    }
}
