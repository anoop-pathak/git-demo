<?php

namespace App\Repositories;

use App\Events\UserActivated;
use App\Events\UserDeactivated;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Contexts\Context;
use App\Services\Users\AuthenticationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Models\Company;
use App\Events\SubContractorGroupChanged;
use App\Exceptions\UpdateUserNotAllowedException;
use App;
use App\Services\Subscriptions\SubscriptionServices;
use App\Events\UserSaveSignature;

class UserRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    protected $authService;

    function __construct(User $model, Context $scope, AuthenticationService $authService){
        $this->model = $model;
        $this->scope = $scope;
        $this->authService = $authService;
    }

    /**
     * @TODO - Re Consider the scope assignment user
     * Register a new User in the system
     * @param $userDetails | Array of user details i.e name, email address, password etc
     * @param $userProfileDetail | array of User Profile i.e address details
     * @param $group | group for that user
     * valid entry for group are
     * User::GROUP_ADMIN, User::GROUP_STANDARD_USER, User::GROUP_SUB_CONTRACTOR
     * @param $company Optional | int id of the company for the user
     * @param $departments Optional array of departments for the user. Required only in case of ROLE_STANDARD_USER
     */
    public function register($userDetails, $userProfileDetails = [], $group, $companyId = null, $departments = [], $productId = null)
    {

        if ($group == User::GROUP_SUPERADMIN) {
            throw new \Exception("New user under this group are not accepted");
        }

        $user = new User($userDetails);
        $user->company_id = $companyId;
        $user->group_id = $group;
        $user->save();

        if (!empty($userProfileDetails)) {
            $profile = new UserProfile($userProfileDetails);
            $profile->user_id = $user->id;
            $profile->save();
        }

        if (!empty($departments)) {
            $user->departments()->attach($departments);
        }
        $this->assignRole($user, $productId);
        Event::fire('JobProgress.Users.Events.UserSaveSignature', new UserSaveSignature($user));

        return $user;
    }

    public function update($userId, $userDetails, $userProfileDetails, $departments)
    {

        $user = User::find($userId);
        $oldUser = clone $user;

        if ($user->group_id == USER::GROUP_SUPERADMIN) {
            throw new \Exception("User under this group are not accepted");
        }
        $user->update($userDetails);
        $user->profile->update($userProfileDetails);
        $user->departments()->detach();
        $user->departments()->attach($departments);

        // update user details in all companies
		if($user->multiple_account) {
			$this->updateAllUserAccounts($user, $oldUser->email);
		}

        // destroy user session
        if (ine($userDetails, 'password') || ine($userDetails, 'email')) {
            if ($userId == \Auth::id()) {
                $this->authService->logoutFromOtherDevices(Auth::user());
            } else {
                $this->authService->logoutFromAllDevices($user->id);
            }
        }

        // $this->assignRole($user);
        return $user;
    }

    public function assignRole(User $user, $product = null)
    {

        if ($user->isSubContractor() || $user->isSubContractorPrime()) {
            $role = Role::byName('sub-contractor');
            $user->attachRole($role);
        }

        if ($user->isNonLoggable() || $user->isSubContractorPrime()) {
            return;
        }

        $user->roles()->detach();
        // if super admin assign super-admin role
        if ($user->isSuperAdmin()) {
            $role = Role::byName('super-admin');
            $user->attachRole($role);
            return true;
        }

        // if product id is null detect product id.
        if (!$product) {
            $company = $user->company;
            if (!$company) {
                return false;
            }
            $subscription = $company->subscription;
            if (!$subscription) {
                return false;
            }
            $product = $subscription->product_id;
        }

        if ($user->isAuthority()) {
            if (($product == Product::PRODUCT_JOBPROGRESS)
                || ($product == Product::PRODUCT_JOBPROGRESS_BASIC_FREE)
            ) {
                $role = Role::byName('basic-admin');
            } elseif (($product == Product::PRODUCT_JOBPROGRESS_PLUS)
                || ($product == Product::PRODUCT_JOBPROGRESS_PLUS_FREE)
                || ($product == Product::PRODUCT_GAF_PLUS)
                || ($product == Product::PRODUCT_JOBPROGRESS_STANDARD)
                || ($product == Product::PRODUCT_JOBPROGRESS_PARTNER)
                || ($product == Product::PRODUCT_JOBPROGRESS_MULTI)
                || ($product == Product::PRODUCT_JOBPROGRESS_25)
            ) {
                $role = Role::byName('plus-admin');
            }
        } elseif ($user->isStandardUser()) {
            if (($product == Product::PRODUCT_JOBPROGRESS)
                || ($product == Product::PRODUCT_JOBPROGRESS_BASIC_FREE)
            ) {
                $role = Role::byName('basic-standard');
            } elseif (($product == Product::PRODUCT_JOBPROGRESS_PLUS)
                || ($product == Product::PRODUCT_JOBPROGRESS_PLUS_FREE)
                || ($product == Product::PRODUCT_GAF_PLUS)
                || ($product == Product::PRODUCT_JOBPROGRESS_STANDARD)
                || ($product == Product::PRODUCT_JOBPROGRESS_PARTNER)
                || ($product == Product::PRODUCT_JOBPROGRESS_MULTI)
                || ($product == Product::PRODUCT_JOBPROGRESS_25)
            ) {
                $role = Role::byName('plus-standard');
            }
        } elseif ($user->isOpenAPIUser()) {
            $role = Role::byName('open-api');
        }

        $user->attachRole($role);
    }

    public function assignTags(User $user, $tagIds)
	{
		$user->tags()->detach();
		$tagIds = arry_fu((array)$tagIds);

        if(empty($tagIds)) return $user;

        $user->tags()->attach($tagIds, ['company_id' => getScopeId()]);

        return $user;
	}

    public function getFilteredUsers($filters, $sortable = true)
    {
        $includeSubContractors = ine($filters, 'include_sub_contractors');
        $users = $this->getUsers($sortable, false, $includeSubContractors);

        $this->applyFilters($users, $filters);

        return $users;
    }

    public function getUsersQueryBuilder($filters = [], $joins = [])
    {
        $with = $this->getOptimizedIncludesData($filters);
        $query = $this->make($with)->sortable()->loggable();

        $this->applyFilters($query, $filters);
        return $query;
    }

    public function getUsers($sortable = true, $withJobsCountsAsCR = false, $includeSubContractors = false, $filters = [])
    {
        $with = $this->getIncludesData($filters);
        $users = $this->make(['profile'])->loggable($includeSubContractors);

        if ($sortable) {
            $users->sortable();
        }

        $users->leftjoin('user_profile as profile', 'profile.user_id', '=', 'users.id');

        if ($withJobsCountsAsCR) {
            $users->leftJoin('customers', 'customers.rep_id', '=', 'users.id')
                ->leftJoin('jobs', 'jobs.customer_id', '=', 'customers.id')
                ->select(DB::raw('users.*,COUNT(jobs.id) As jobs_count_as_cr'))
                ->groupBy('users.id');
        } else {
            $users->select('users.*');
        }

        if (!\Auth::user()->isSuperAdmin()) {
            $users->notHiddenUser();
        }

        $users->NotOpenAPISUser();

        return $users;
    }

    /**
     * Activate the user
     * @param  User $user | Instance of user model
     * @return void
     */
    public function activate(User $user)
    {
        if ($user->active) {
            return true;
        }
        $user->active = true;
        $user->save();

        // User Activated Event.
        Event::fire('JobProgress.Users.Events.UserActivated', new UserActivated($user));
        return true;
    }

    /**
     * Deactivate the user
     * @param  User $user | Instance of user model
     * @return void
     */
    public function deactivate(User $user)
    {
        if (!$user->active) {
            return true;
        }
        $user->active = false;
        $user->save();

        // // distroy session for this user
        DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();

		// remove all mobile device tokens
		DB::table('user_devices')
			->where('user_id', $user->id)
			->delete();

        // User Deactivated Event.
        Event::fire('JobProgress.Users.Events.UserDeactivated', new UserDeactivated($user));
        return true;
    }

    public function createAnonymous(Company $company, $userDetails, $userProfileDetails = [], $productId)
    {
        $userDetails['first_name'] = config('jp.anonymous_fname');
        $userDetails['last_name'] = config('jp.anonymous_lname');
        $subscriber = $company->subscriber;
        $userDetails['email'] = ucfirst(substr(clean($company->name), 0, 1)) . strtolower(clean($subscriber->last_name)) . $company->id . '@jobprogress.com';
        $userDetails['password'] = 'JP' . strtolower(strrev(str_replace(' ', '', $subscriber->last_name)));
        $group = User::GROUP_ANONYMOUS;
        $this->register($userDetails, $userProfileDetails, $group, $company->id, [], $productId);
    }

    /**
     * change subcontractor role (for prime)
     * @param  User   $subContractor
     * @return $subContractor
     */
    public function changeSubContractorGroup(User $subContractor, $groupId)
    {
        if($subContractor->group_id == $groupId) return $subContractor;
        $subContractor->update(['group_id' => $groupId]);
        $role = Role::byName('sub-contractor');
        if($subContractor->isSubContractorPrime()) {
            $role = Role::byName('sub-contractor-prime');
        }
        $subContractor->roles()->detach();
        $subContractor->attachRole($role);
        \Event::fire('JobProgress.SubContractors.Events.SubContractorGroupChanged', new SubContractorGroupChanged($subContractor));
        return $subContractor;
    }

    /**
	 * update user credentials
	 * @param  User 	| $user		| object of a user
	 * @param  String 	| $email    | new email
	 * @param  String 	| $password | new password
	 * @return $user
	 */
	public function updateCredentials($user, $input)
	{
		// $oldEmail = $user->email;
		// if(ine($input, 'email')) {
		// 	$user->email = $input['email'];
		// }
		// if(ine($input, 'new_password')) {
		// 	$user->password = $input['new_password'];
		// }
		// $user->save();
		// $user->profile->update($userProfileDetails);
		// if($user->multiple_account) {
		// 	$user = $this->updateAllUserAccounts($user, $oldEmail, $credentials = true);
		// }
		// // destroy user session
		// if (ine($input, 'password')) {
		// 	if ($user->id  == \Auth::id()) {
		// 		$this->authService->logoutFromOtherDevices($user->id);
		// 	} else {
		// 		$this->authService->logoutFromAllDevices($user->id);
		// 	}
		// }
		return $user;
	}

    /**
     * Add User Divisions
     * @param  User $user | User instance
     * @param  Array $divisions | Division Ids
     * @return User $user
     */
    public function assignDivisions($user, $divisionsIds, $allDivisionAccess=false)
    {
 		$user->divisions()->sync(arry_fu($divisionsIds));
		$user->all_divisions_access = $allDivisionAccess;
		$user->save();
 		return $user;
    }

    /**
     * update user detail in all companies
     * @param  User 	| $user     | Object of User model
     * @param  String 	| $oldEmail | old email of user
     * @return $user
     */
    public function updateAllUserAccounts($user, $oldEmail)
	{
		$data = [
			'first_name' => $user->first_name,
			'last_name'	 => $user->last_name,
			'email'		 => $user->email,
			'password'	 => $user->password,
		];
		$profileData = [
			'phone'				=> $user->profile->phone,
			'cell'				=> $user->profile->cell,
			'address'			=> $user->profile->address,
			'address_line_1'	=> $user->profile->address_line_1,
			'city'				=> $user->profile->city,
			'state_id'			=> $user->profile->state_id,
			'zip'				=> $user->profile->zip,
			'country_id'		=> $user->profile->country_id,
			'position'			=> $user->profile->position,
			'additional_phone'	=> $user->profile->additional_phone,
			'profile_pic'		=> $user->profile->profile_pic,
		];
		User::where('email', $oldEmail)
			->where('id', '<>', $user->id)
			->update($data);
		$users = User::where('email', $user->email)
			->where('id', '<>', $user->id)
			->get();
		if($users->isEmpty()) return $user;
		$subscriptionService = App::make(SubscriptionServices::class);
		foreach ($users as $value) {
			$value->profile->update($profileData);
			if($value->group_id != User::GROUP_OWNER) continue;
			$subscriptionService->updateSubscriptionAccount($value);
			$company = $value->company;
			$anonymous = $company->anonymous;
			if($anonymous) {
				$anonymous->email = ucfirst(substr(clean($company->name), 0,1)).strtolower(clean($value->last_name)).$company->id.'@jobprogress.com';
				$anonymous->password = 'JP'.strtolower(strrev(str_replace(' ', '', $value->last_name)));
				$anonymous->save();
			}
		}
		return $user;
    }

    public function resetPassword($user, $newPassword)
	{
		$user->password = $newPassword;
		$user->save();
		$this->authService->logoutFromAllDevices($user->id);
	}


    /************** Private Functions ****************/
    private function getIncludesData($filters = array())
	{
		$with = ['profile', 'group', 'departments', 'profile.state', 'profile.country', 'role', 'company'];
        if(!ine($filters, 'includes')) return $with;

		$includes = (array)$filters['includes'];

        if(in_array('tags', $includes)) {
			$with[] = 'tags';
        }

        if(in_array('primary_device', $includes)) {
			$with[] = 'primaryDevice';
		}

        return $with;
    }

    private function getOptimizedIncludesData($filters = array())
	{
		$with = ['profilePic', 'group'];
		if(!ine($filters, 'includes')) return $with;

		$includes = (array)$filters['includes'];

		if(in_array('tags', $includes)) {
			$with[] = 'tags';
		}

		return $with;
	}

    private function applyFilters($query, $filters)
    {
        $query->division();

        if (ine($filters, 'keyword')) {
            $query->where('first_name', 'like', '%' . $filters['keyword'] . '%');
        }

        if ((!isset($filters['active'])
                || (isset($filters['active'])
                    && ($filters['active'] == 1 || $filters['active'] == 'true')))
            && !ine($filters, 'inactive')
            && !ine($filters, 'with_inactive')) {
            $query->active();
        }

        if (ine($filters, 'inactive')) {
            $query->active(false);
        }

        if (ine($filters, 'user_ids')) {
            $query->whereIn('users.id', $filters['user_ids']);
        }

        // by email and full name
        if (ine($filters, 'query')) {
            $query->where(function ($query) use ($filters) {
                $query->where('email', 'Like', '%' . $filters['query'] . '%');
                $query->orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?", ['%' . $filters['query'] . '%']);
            });
        }

        if(ine($filters, 'user_id')) {
            $query->whereIn('users.id', (array)$filters['user_id']);
        }

        if(ine($filters, 'only_sub_contractors')) {
            $query->onlySubContractors();
        }

        if(ine($filters, 'tags')) {
            $query->tags($filters['tags']);
        }

        if(ine($filters, 'without_tags')) {
            $query->withoutTags();
        }

        $query->notHiddenUser();
    }

    /**
     * Create Open API user
     */

    public function createOpenAPIUser(Company $company, $userDetails, $userProfileDetails = [], $productId)
    {
        $userDetails['first_name'] = 'Open';
        $userDetails['last_name'] = 'API';
        $subscriber = $company->subscriber;
        $userDetails['email'] = ucfirst(substr(clean($company->name), 0, 1)) . strtolower(clean($subscriber->last_name)) . $company->id . '@jobprogress.com';
        $userDetails['password'] = 'JP' . strtolower(strrev(str_replace(' ', '', $subscriber->last_name)));
        $group = User::GROUP_OPEN_API;
        $this->register($userDetails, $userProfileDetails, $group, $company->id, [], $productId);
    }

    /**
     * Check if company has open API user
     * @return Boolean
     */

    public function hasOpenAPIUser($companyId)
    {
        $group = User::GROUP_OPEN_API;

        return User::whereCompanyId($companyId)->whereGroupId($group)->exists();

    }

    /**
     * Check if company has open API user
     * @return User
     */

    public function getOpenAPIUser($companyId)
    {
        $group = User::GROUP_OPEN_API;

        return User::whereCompanyId($companyId)->whereGroupId($group)->first();

    }
}
