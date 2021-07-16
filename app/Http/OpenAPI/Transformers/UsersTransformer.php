<?php

namespace App\Http\OpenAPI\Transformers;

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
    protected $defaultIncludes = ['role'];

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
            'company_id' => (int)$user->company_id,
            'company' => isset($user->company->name) ? $user->company->name : null,
            'admin_privilege' => (bool)$user->admin_privilege,
            'group' => $user->group,
            'added_date' => $user->created_at,
            'profile_pic' => empty($profile->profile_pic) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $profile->profile_pic),
            'active' => (bool)$user->active,
            'company_name' => $user->company_name,
            'color' => $user->color
        ];
    }

    /**
     * Include profile
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProfile($user)
    {
        $profile = $user->profile;

        if ($profile) {
            return $this->item($profile, new UserProfileTransformer);
        }
    }

    public function includeRole($user)
    {
        $role = $user->role;
        
        if($role){
            return $this->item($role, function($roles) {
                $allRoles = [];
                foreach($roles as $role) {
                    $allRoles[] = ['name' => $role->name];
                } 

                return  $allRoles;
            });
        }
    } 
}
