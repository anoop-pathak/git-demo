<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SubscriptionTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['plan'];

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
    public function transform($subscription)
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'activation_date' => $subscription->activation_date,
            'amount' => $subscription->amount,
            'quantity' => $subscription->quantity,
            'setup_fee' => $subscription->setup_fee,
            'subscription_plan_id' => $subscription->subscription_plan_id,
            'status_updated_at'     =>  $subscription->status_updated_at,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includePlan($subscription)
    {
        $plan = $subscription->plan;
        if ($plan) {
            return $this->item($plan, function ($plan) {
                return [
                    'product_id' => $plan->product_id,
                    'title' => $plan->title,
                    'amount' => $plan->amount,
                    'product' => $plan->product->title
                ];
            });
        }
    }
}
