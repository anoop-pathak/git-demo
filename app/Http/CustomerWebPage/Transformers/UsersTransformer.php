<?php

namespace App\Http\CustomerWebPage\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\UsersTransformer as UserTransformer;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;
use App\Transformers\CompaniesTransformer;

class UsersTransformer extends UserTransformer
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
    protected $availableIncludes = [
        'profile'
    ];

    public function transform($user)
    {
        $profile = $user->profile;

        return [
            'id' => (int)$user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ];
    }
}
