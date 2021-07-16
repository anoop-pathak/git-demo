<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SubscribersExportTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($subscriber)
    {
        $data = [
            'Subscriber Id' => $subscriber->id,
            'Company Name' => $subscriber->name,
            'Owner Name' => $subscriber->subscriber->full_name,
            'Owner Email' => $subscriber->subscriber->email,
            'Owner Phone' => isset($subscriber->subscriber->profile) ? $subscriber->subscriber->profile->present()->additionalPhones() : '',
            'Office Email' => $subscriber->present()->additionalEmail(),
            'Office Phone' => $subscriber->present()->additionalPhone(),
            'Office Address' => $subscriber->office_address,
            'Office City' => $subscriber->office_city,
            'Office State' => $subscriber->state->name,
            'Office Zip' => $subscriber->office_zip,
            'Status' => $subscriber->subscription->status,
        ];

        return $data;
    }
}
