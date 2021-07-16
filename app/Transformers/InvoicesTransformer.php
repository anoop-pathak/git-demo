<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class InvoicesTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($invoice)
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'currency' => $invoice->currency,
            'total_ammount' => "$" . ($invoice->total_in_cents / 100),
            'subtotal' => "$" . ($invoice->subtotal_in_cents / 100),
            'state' => $invoice->state,
            'created_at' => $invoice->created_at,
            'closed_at' => $invoice->closed_at,
        ];
    }
}
