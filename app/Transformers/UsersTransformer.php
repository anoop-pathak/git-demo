<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;
use App\Transformers\CompaniesTransformer;
use App\Transformers\TagsTransformer;

class UsersTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['profile'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'google_client',
        'company_details',
        'product',
        'dropbox_client',
        'divisions',
        'all_companies',
        'tags',
        'primary_device',
        'virtual_number'
    ];

    public function transform($user)
    {
        $profile = $user->profile;

        return [
            'id' => (int)$user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'full_name_mobile' => $user->full_name_mobile,
            'email' => $user->email,
            'company_id' => (int)$user->company_id,
            'company' => isset($user->company->name) ? $user->company->name : null,
            'admin_privilege' => (bool)$user->admin_privilege,
            'group' => $user->group,
            'role' => $user->role,
            'departments' => $user->departments,
            'added_date' => $user->created_at,
            'profile_pic' => empty($profile->profile_pic) ? null : FlySystem::publicUrl(config('jp.BASE_PATH') . $profile->profile_pic),
            'active' => (bool)$user->active,
            'company_name' => $user->company_name,
            'color' => $user->color,
            'commission_percentage' => $user->commission_percentage,
            'resource_id'           => $user->resource_id,
            'data_masking'          => $user->dataMaskingEnabled(),
            'all_divisions_access'  => (bool)$user->all_divisions_access,
            'multiple_account'      => $user->multiple_account,
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

    /**
     * Include Primary Device
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

    /**
     * Include Google Account of User
     *
     * @return League\Fractal\ItemResource
     */
    public function includeGoogleClient($user)
    {
        $googleClient = $user->googleClient;

        if ($googleClient) {
            return $this->item($googleClient, function ($googleClient) {
                return [
                    'id' => $googleClient->id,
                    'email' => $googleClient->email,
                    'scope_calendar_and_tasks' => $googleClient->scope_calendar_and_tasks,
                    'scope_drive' => $googleClient->scope_drive,
                    'scope_gmail' => $googleClient->scope_gmail,
                    'two_way_syncing_enabled' => (bool)$googleClient->channel_id,
                ];
            });
        }
    }

    public function includeDropboxClient($user)
    {
        $dropboxClient = $user->dropboxClient;
        if ($dropboxClient) {
            return $this->item($dropboxClient, function ($dropboxClient) {
                return [
                    'id' => $dropboxClient->id,
                    'email' => $dropboxClient->user_name,
                ];
            });
        }
    }

    public function includeCompanyDetails($user)
    {
        $company = $user->company;

        if ($company) {
            return $this->item($company, new CompaniesTransformer);
        }
    }

    public function includeProduct($user)
    {
        $company = $user->company;
        if ($company) {
            $product = $company->subscription->product;
            return $this->item($product, function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title
                ];
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

        return $this->collection( $divisions, new DivisionsTransformerOptimized);
    }

    public function includeAllCompanies($user)
    {
        $companies = $user->allCompanies();
        return $this->collection($companies, new CompaniesTransformer);
    }

    /**
    * Include Tags
    *
    */
    public function includeTags($user)
    {
        $tags = $user->tags;

        return $this->collection($tags, new TagsTransformer);
    }

    public function includeVirtualNumber($user)
    {
        $virtualNumber = $user->virtualNumber;
        if($virtualNumber) {
            return $this->item($virtualNumber, function($virtualNumber) {
                return [
                    'twilio_phone_number' => $virtualNumber->phone_number,
                    'user_id' => $virtualNumber->user_id
                ];
            });
        }
    }
}
