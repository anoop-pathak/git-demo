<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\SubContractorsTransformer as SubContractorsTransformers;

class SubContractorsTransformer extends SubContractorsTransformers
{
    protected $defaultIncludes = [
        'trades',
        'work_types'
    ];
    
    protected $availableIncludes = [
        'role'
    ];
    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($subContractor)
    {
        $profile = $subContractor->profile;
        return [
            'id'                => $subContractor->id,
            'first_name'        => $subContractor->first_name,
            'last_name'         => $subContractor->last_name,
            'email'             => $subContractor->email,
            'company_name'      => $subContractor->company_name,
            'additional_phone'  => isset($profile->additional_phone) ? $profile->additional_phone : null,
            'address'           => isset($profile->address) ? $profile->address : null,
            'address_line_1'    => isset($profile->address_line_1) ? $profile->address_line_1 : null,
            'zip'               => zipCodeFormat($profile->zip, $profile->country_id),
            'city'              => $profile->city,
            'country'           => $profile->country,
            'state'             => $profile->state,
            'is_active'         => (bool)$subContractor->active,
            'profile_pic'       => empty($profile->profile_pic) ? null : \FlySystem::publicUrl(\Config::get('jp.BASE_PATH').$profile->profile_pic),
            'created_at'        => $subContractor->created_at,
            'updated_at'        => $subContractor->updated_at,
            'note'              => $subContractor->note,
            'rating'            => $subContractor->rating,
            'color'             => $subContractor->color,
            'group_id'          => $subContractor->group_id
        ];
    }
   
    public function includeRole($subContractor)
    {
        $role = $subContractor->role->first();
        if($role){
            return $this->item($role, function($role) {
                return [$role];
            });
        }
    } 

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($subContractor)
    {
        $trades = $subContractor->laborTrades;
        if($trades) {
			return $this->collection($trades, function($trade) {
                return [
                	'id'   => $trade->id,
                	'name' => $trade->name,
                ];
            }); 
        }
    }

    /**
     * Include workTypes
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkTypes($subContractor) {
        $worktypes = $subContractor->laborWorkTypes;
        if($worktypes){

            return $this->collection($worktypes, new JobTypesTransformer);
        }
    }
}