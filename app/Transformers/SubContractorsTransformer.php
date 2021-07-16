<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\SubContractorRateSheetTransformer;
use Config;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;

class SubContractorsTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];
    protected $availableIncludes = [
        'rate_sheet',
        'role',
        'divisions',
        'tags'
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
            'full_name'         => $subContractor->full_name,
            'full_name_mobile'  => $subContractor->full_name_mobile,
            'email'             => $subContractor->email,
            'resource_id'       => $subContractor->resource_id,
            'company_name'      => $subContractor->company_name,
            'hire_date'         => $subContractor->hire_date,
            'additional_phone'  => isset($profile->additional_phone) ? $profile->additional_phone : null,
            'address'           => isset($profile->address) ? $profile->address : null,
            'address_line_1'    => isset($profile->address_line_1) ? $profile->address_line_1 : null,
            'zip'               => zipCodeFormat($profile->zip, $profile->country_id),
            'city'              => $profile->city,
            'country'           => $profile->country,
            'country_id'        => isset($profile->country_id) ? $profile->country_id : null,
            'state'             => $profile->state,
            'state_id'          => isset($profile->state_id) ? $profile->state_id : null,
            'type'              => 'sub_contractor',
            'is_active'         => (bool)$subContractor->active,
            'profile_pic'       => empty($profile->profile_pic) ? null : \FlySystem::publicUrl(\Config::get('jp.BASE_PATH').$profile->profile_pic),
            'trades'            => $subContractor->laborTrades,
            'created_at'        => $subContractor->created_at,
            'updated_at'        => $subContractor->updated_at,
            'note'              => $subContractor->note,
            'rating'            => $subContractor->rating,
            'work_types'        => $subContractor->laborWorkTypes,
            'color'             => $subContractor->color,
            'group_id'          => $subContractor->group_id,
            'has_password'		=> (bool)$subContractor->password,
            'data_masking'      => $subContractor->dataMaskingEnabled(),
            'all_divisions_access'	=> $subContractor->all_divisions_access,
        ];
    }
     /**
     * Include Sub Contractor Rate Sheet
     *
     * @return League\Fractal\ItemResource
     */
    public function includeRateSheet($subContractor)
    {
        $financialDetails = $subContractor->financialDetails;
        if(!$financialDetails->isEmpty()){
            return $this->collection($financialDetails, new SubContractorRateSheetTransformer);
        }
    }
    /**
     * Include Sub Contractor Role
     *
     * @return League\Fractal\ItemResource
     */
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

    /**
    * Include Tags
    *
    */
    public function includeTags($subContractor)
    {
        $tags = $subContractor->tags;

        return $this->collection($tags, new TagsTransformer);
    }
}