<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;

class UserFormTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['divisions'];

    public function transform($user)
    {
        return [
            'id' => (int)$user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->last_name . ', ' . $user->first_name,
            'full_name_mobile' => $user->last_name . ', ' . $user->first_name,
            'email' => $user->email,
            'company_id' => (int)$user->company_id,
            'company' => $user->company->name,
            'admin_privilege' => (bool)$user->admin_privilege,
            'group' => $user->group,
            'departments' => $user->departments,
            'added_date' => $user->created_at,
            'phone' => $user->profile->phone,
            'additional_phone' => $user->profile->additional_phone,
            'address' => $user->profile->address,
            'address_line_1' => $user->profile->address_line_1,
            'city' => $user->profile->city,
            'state_id' => (int)$user->profile->state_id,
            'state' => $user->profile->state->name,
            'country_id' => (int)$user->profile->country_id,
            'country' => $user->profile->country->name,
            'zip' => $user->profile->zip,
            'position' => $user->profile->position,
            'profile_pic' => empty($user->profile->profile_pic) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $user->profile->profile_pic)
        ];
    }

    /**
     * Include Division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivisions($user)
    {
        $divisions = $user->divisions;

        if(!$divisions->isEmpty()){

            return $this->collection($divisions, new DivisionsTransformerOptimized);
        }
    }
}
