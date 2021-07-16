<?php

namespace App\Transformers;

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
            'full_name' => $contact->full_name,
            'full_name_mobile' => $contact->full_name_mobile,
            // 'email'             => $contact->email,
            // 'phones'            => $contact->phones,
            // 'address'           => $contact->address,
            // 'address_line_1'    => $contact->address_line_1,
            // 'zip'               => zipCodeFormat($contact->zip, $contact->country_id),
            // 'city'              => $contact->city,
            // 'country_id'        => $contact->country_id,
            // 'email'             => $contact->email,
            // 'country'           => $contact->country,
            // 'state'             => $contact->state,
            // 'additional_emails' => $contact->additional_emails,
        ];
    }
}
