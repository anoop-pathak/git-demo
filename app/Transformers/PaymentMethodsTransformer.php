<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class PaymentMethodsTransformer extends TransformerAbstract
{
    public function transform($method)
    {
        return [
            'id' => $method->id,
            'label' => $method->label,
            'method' => $method->method,
            'company_id' => $method->company_id,
            'quickbook_id' => $method->quickbook_id,
        ];
	}
}