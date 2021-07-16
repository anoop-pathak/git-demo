<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class UserDevicesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($device)
    {
        $model = $device->model;
        $iphoneFamilyList = \config('iphone-family-list');
        if (array_key_exists($model, $iphoneFamilyList)) {
            $model = $iphoneFamilyList[$model];
        }
        return [
            'id'  			    => $device->id,
			'user_id'  		    => $device->user_id,
			'uuid'  		    => $device->uuid,
			'app_version'  	    => $device->app_version,
			'platform'  	    => $device->platform,
			'manufacturer'      => $device->manufacturer,
			'os_version'  	    => $device->os_version,
			'is_primary_device' => (int) $device->is_primary_device,
			'model'  		    => $model
        ];
    }

    /**
     * Include user
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUser($device)
    {
        $user = $device->user;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }
}
