<?php

namespace App\Transformers;

use App\Models\User;
use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;

class LabourTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    protected $availableIncludes = [
        'rate_sheet',
        'divisions'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($labour)
    {
        $profile = $labour->profile;

        return [
            'id' => $labour->id,
            'first_name' => $labour->first_name,
            'last_name' => $labour->last_name,
            'full_name' => $labour->full_name,
            'full_name_mobile' => $labour->full_name_mobile,
            'email' => $labour->email,
            'resource_id' => $labour->resource_id,
            'company_name' => $labour->company_name,
            'hire_date' => $labour->hire_date,
            'additional_phone' => isset($profile->additional_phone) ? $profile->additional_phone : null,
            'address' => isset($profile->address) ? $profile->address : null,
            'address_line_1' => isset($profile->address_line_1) ? $profile->address_line_1 : null,
            'zip' => zipCodeFormat($profile->zip, $profile->country_id),
            'city' => isset($profile->city) ? $profile->city : null,
            'country' => isset($profile->country) ? $profile->country : null,
            'country_id' => isset($profile->country_id) ? $profile->country_id : null,
            'state' => isset($profile->state) ? $profile->state : null,
            'state_id' => isset($profile->state_id) ? $profile->state_id : null,
            'type' => ($labour->group_id == User::GROUP_LABOR) ? 'labor' : 'sub_contractor',
            'is_active' => (bool)$labour->active,
            'profile_pic' => empty($profile->profile_pic) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $profile->profile_pic),
            'trades' => $labour->laborTrades,
            'created_at' => $labour->created_at,
            'updated_at' => $labour->updated_at,
            'note' => $labour->note,
            'rating' => $labour->rating,
            'work_types' => $labour->laborWorkTypes,
            'color' => $labour->color,
        ];
    }

    /**
     * Include Sub Contractor Rate Sheet
     *
     * @return League\Fractal\ItemResource
     */
    public function includeRateSheet($labour)
    {
        $financialDetails = $labour->financialDetails;
        if (!$financialDetails->isEmpty()) {
            return $this->collection($financialDetails, function ($financialDetail) {
                return [
                    'id' => $financialDetail->id,
                    'unit' => $financialDetail->unit,
                    'unit_cost' => $financialDetail->unit_cost,
                    'description' => $financialDetail->description,
                    'selling_price' => $financialDetail->selling_price,
                ];
            });
        }
    }

    /**
     * Include Division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivisions($labour)
    {
        if($labour->all_divisions_access){
           $divisions = Division::whereCompanyId($labour->company_id)->get();
        }else{
            $divisions = $labour->divisions;
        }
         if(!$divisions->isEmpty()){
             return $this->collection( $divisions, new DivisionsTransformerOptimized);
        }
    }
}
