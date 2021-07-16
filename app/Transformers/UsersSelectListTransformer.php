<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\UserProfileTransformer;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Models\Division;

class UsersSelectListTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['tags'];

    public function transform($user) {
        $profile = $user->profilePic;

        return [
            'id'                => (int)$user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
            'full_name'         => $user->full_name,
            'full_name_mobile'  => $user->full_name_mobile,
            'group'             => $user->group,
            'profile_pic'       => empty($profile->profilePic) ? Null : \FlySystem::publicUrl(config('jp.BASE_PATH').$profile->profile_pic),
            'color'             =>  $user->color,
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

        if($profile) {
            return $this->item($profile, new UserProfileTransformer);
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

        if($googleClient) {
            return $this->item($googleClient, function($googleClient) {
               return [
                    'id'                               => $googleClient->id,
                    'email'                            => $googleClient->email,
                    'scope_calendar_and_tasks'         => $googleClient->scope_calendar_and_tasks,
                    'scope_drive'                      => $googleClient->scope_drive,
                    'scope_gmail'                      => $googleClient->scope_gmail,
                    'two_way_syncing_enabled'          => (bool)$googleClient->channel_id,
               ];
            });
        }
    }

    public function includeDropboxClient($user)
    {
        $dropboxClient = $user->dropboxClient;
        if($dropboxClient) {
            return $this->item($dropboxClient, function($dropboxClient) {
               return [
                    'id'                               => $dropboxClient->id,
                    'email'                            => $dropboxClient->user_name,
               ];
            });
        }
    }

    public function includeCompanyDetails($user) {
        $company = $user->company;
        if($company) {
            return $this->item($company, new CompaniesTransformer);
        }
    }

    public function includeProduct($user) {
        $company = $user->company;
        if($company) {
            $product = $company->subscription->product;
            return $this->item($product, function($product) {
               return [
                    'id'    => $product->id,
                    'title' => $product->title
               ];
            });
        }
    }

    public function includeAllCompanies($user)
    {
        $companies = $user->allCompanies();

        return $this->collection($companies, new CompaniesTransformer);
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

    /**
    * Include Tags
    *
    */
    public function includeTags($user)
    {
        $tags = $user->tags;

        return $this->collection($tags, new TagsTransformer);
    }
}