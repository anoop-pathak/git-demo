<?php

namespace App\Http\OpenAPI\Transformers;

use FlySystem;
use App\Transformers\UserProfileTransformer as ProfileTransformer;
use League\Fractal\TransformerAbstract;

class UserProfileTransformer extends ProfileTransformer
{

    public function transform($userProfile)
    {
        return [
            'id' => (int)$userProfile->id,
            'user_id' => (int)$userProfile->user_id,
            'phone' => $userProfile->phone,
            'additional_phone' => $userProfile->additional_phone,
            'address' => $userProfile->address,
            'address_line_1' => $userProfile->address_line_1,
            'city' => $userProfile->city,
            'state_id' => (int)$userProfile->state_id,
            'state' => $userProfile->state->name,
            'country_id' => (int)$userProfile->country_id,
            'country' => $userProfile->country->name,
            'zip' => $userProfile->zip,
            'position' => $userProfile->position,
            'profile_pic' => empty($userProfile->profile_pic) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $userProfile->profile_pic),
        ];
    }
}
