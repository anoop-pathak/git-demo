<?php

namespace App\Transformers\Optimized;

use App\Transformers\JobCommissionsTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;

class UsersTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['commissions', 'divisions'];

    public function transform($user)
    {
        return [
            'id' => (int)$user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'full_name_mobile' => $user->full_name_mobile,
            'email' => $user->email,
            'group_id' => $user->group_id,
            'profile_pic' => $user->getUserProfilePic(),
            'color' => $user->color,

            // company_name for labor/subs
            'company_name' => $user->company_name,

            // only will add when joined through query..
            'total_commission' => $user->total_commission,
            'paid_commission' => $user->paid_commission,
            'unpaid_commission' => $user->unpaid_commission,
            'group_id'          => $user->group_id,
            'all_divisions_access'  => (bool)$user->all_divisions_access,
            // status for job schedules confirmation
            'status' => isset($user['pivot']) ? $user['pivot']['status'] : null,
        ];
    }

    /**
     * Include Commissions
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCommissions($user)
    {
        $commissions = $user->commissions;
        $transformer = (new JobCommissionsTransformer)->setDefaultIncludes(['job']);
        return $this->collection($commissions, $transformer);
    }

    /**
     * Include Division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivisions($user)
    {
       if($user->all_divisions_access){
           $divisions = Division::whereCompanyId($user->company_id)->get();
        }else{
            $divisions = $user->divisions;
        }
         if(!$divisions->isEmpty()){
            return $this->collection( $divisions, new DivisionsTransformerOptimized);
        }
    }
}
