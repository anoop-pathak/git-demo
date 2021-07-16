<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class MobileAppsTransformer extends TransformerAbstract
{

    public function transform($mobileApp)
    {
        return [
            'id' => $mobileApp->id,
            'device' => $mobileApp->device,
            'version' => $mobileApp->version,
            'description' => $mobileApp->description,
            'forced' => $mobileApp->forced,
            'url' => $mobileApp->url,
            'approved' => $mobileApp->approved,
        ];
    }
}
