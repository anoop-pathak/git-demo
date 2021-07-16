<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class CompanyJobInvoiceTransformer extends TransformerAbstract
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
    public function transform($number)
    {
        return [
            'start_from' => $number->start_from,
            'current_serial_number' => $number->start_from + $number->current_number,
        ];
    }
}
