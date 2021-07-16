<?php

namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;

class CustomerContactsTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($contact)
    {

        return [
            'id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
        ];
    }
}