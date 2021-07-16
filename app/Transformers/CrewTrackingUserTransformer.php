<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\Division;
use FlySystem;

class CrewTrackingUserTransformer extends TransformerAbstract {

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['tags', 'divisions', 'phones'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
       'primary_device'
    ];

    public function transform($user) {
        $profile = $user->profile;

        return [
            'id'                => (int)$user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'full_name'         => $user->full_name,
            'profile_pic'       => empty($profile->profile_pic) ? Null : FlySystem::publicUrl(config('jp.BASE_PATH').$profile->profile_pic),
            'color'             => $user->color,
        ];
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

        return $this->collection($divisions, function($division) {
                return [
                    'id'  => $division->id,
                ];  
            });    
    }

    /**
    * Include Tags
    *
    */
    public function includeTags($user)
    {
        $tags = $user->tags;

        return $this->collection($tags, function($tag) {
                return [
                    'id'  => $tag->id,
                ];  
            });
    }

    /**
     * Include profile
     *
     * @return League\Fractal\ItemResource
     */
    public function includePrimaryDevice($user)
    {
        $device = $user->primaryDevice;

        if($device) {
            return $this->item($device, (new UserDevicesTransformer)->setDefaultIncludes([]));
        }
    }

    public function includePhones($user){
        $profile = $user->profile;
        if($profile) {
            return $this->item($profile, function($profile) {
                return [
                    'phone' =>  $profile->phone,
                    'additional_phone'  =>  $profile->additional_phone,
                ];
            }); 
        }
    }
}