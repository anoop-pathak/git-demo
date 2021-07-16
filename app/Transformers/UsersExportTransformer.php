<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class UsersExportTransformer extends TransformerAbstract
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($users)
    {
        $data = [
            'Name' => $users->full_name,
            'Company' => isset($users->company) ? $users->company->name : '',
            'Role' => isset($users->group) ? $users->group->name : '',
            'Position' => isset($users->profile) ? $users->profile->position : '',
            'Phone' => isset($users->profile) ? $users->profile->present()->additionalPhones() : '',
            'Email' => $users->email,
            'Address' => isset($users->profile) ? $users->profile->address : '',
            'City' => isset($users->profile) ? $users->profile->city : '',
            'State' => isset($users->profile->state) ? $users->profile->state->name : '',
            'Zip' => isset($users->profile) ? $users->profile->zip : '',
        ];

        return $data;
    }
}
