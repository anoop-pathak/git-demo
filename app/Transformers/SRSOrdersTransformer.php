<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SRSOrdersTransformer extends TransformerAbstract
{
    public function transform($order)
    {
        return [
            'id' => $order->id,
            'material_list_id' => $order->material_list_id,
            'order_id' => $order->order_id,
            'order_status' => $order->order_status,
            'order_details' => $order->order_details,
        ];
    }
}
