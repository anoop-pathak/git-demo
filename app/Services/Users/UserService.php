<?php
namespace  App\Services\Users;

use App\Repositories\JobRepository;
use App\Exceptions\UnassignDivisionException;
use Illuminate\Support\Facades\DB;
use App\Repositories\UserInvitationsRepository;
use App\Repositories\UserRepository;
use App\Services\Subscriptions\SubscriptionServices;
use App\Events\UserWasCreated;
use App\Models\UserInvitation;
use App\Models\Company;
use App\Models\User;
use App\Services\Users\AuthenticationService;
use App\Models\Subscription;
use App;
use Illuminate\Support\Facades\Hash;
use App\Models\UserDevice;
use App\Models\Resource;
use App\Services\Resources\ResourceServices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Excel;
use Illuminate\Support\Facades\Validator;
use App\Services\Grid\CommanderTrait;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\State;
use Illuminate\Support\Facades\Event;
use App\Models\Role;
use App\Models\Country;
use App\Events\SubContractorGroupChanged;
use App\Exceptions\PasswordNotMatchException;
use App\Exceptions\NewPasswordShouldDifferentException;
use App\Commands\UserCreateCommand;

class UserService
{
	use CommanderTrait;

    protected $repo;
    protected $invitationRepo;
    protected $subscriptionService;
    protected $resourceService;

    public function __construct(UserRepository $repo,
        UserInvitationsRepository $invitationRepo,
        SubscriptionServices $subscriptionService,
        ResourceServices $resourceService,
        JobRepository $jobRepo
    ){
        $this->repo = $repo;
		$this->invitationRepo = $invitationRepo;
		$this->subscriptionService = $subscriptionService;
		$this->resourceService = $resourceService;
        $this->jobRepo = $jobRepo;
    }

    /**
	 * send invitation of a new company to user
	 * @param  User 	$user
	 * @param  Array 	$input
	 * @return $invitation
	 */
	public function sendInvitation($user, $company, $groupId)
	{
		$invitation = $this->invitationRepo->save($user, $groupId);
		$this->sendInvitationMail($user, $invitation, $company);
		return $invitation;
    }

    /**
	 * accept/reject invitation by user
	 * @param  UserInvitation 	$invitation
	 * @param  Array 			$data
	 * @return $invitation
	 */
	public function acceptInvitation($invitation)
	{
		$data['status'] = UserInvitation::ACCEPTED;
		$invitation = $this->invitationRepo->update($invitation, $data);

        setScopeId($invitation->company_id);

        $this->createNewUser($invitation->user, $invitation);

		return $invitation;
    }

    /**
	 * get user invitation by id
	 * @param  Integer	$id
	 * @return $invitation
	 */
	public function getInvitationByToken($token)
	{
        $invitation = UserInvitation::where('token', $token)->firstOrFail();

		return $invitation;
    }

    /**
	 * get all user invitations
	 * @return $list
	 */
	public function invitationList()
	{
		$user = Auth::user();
        $list = $user->invitations();

		return $list;
    }

    /**
	 * get company list of a user
	 * @return $companies
	 */
	public function companyList()
	{
		$user = Auth::user();
		$companies = Company::whereIn('id', function($query) use($user){
			$query->select('company_id')
				->from('users')
				->where('email', $user->email)
				->where('active', true)
				->whereNull('users.deleted_at');

			if(config('is_mobile')) {
				$query->where('group_id', '<>', User::GROUP_SUB_CONTRACTOR);
			}
		})->activated(Subscription::ACTIVE);
		return $companies;
    }

    /**
	 * switch account of a user to another company
	 * @param  User 	| $user 	| User object
	 * @param  String 	| $token 	| Access token of a user
	 * @return boolean
	 */
	public function switchAccount($user, $token, $deviceTokenId = null)
	{
		$token = Auth::user()->token()->id;
		$authService = new AuthenticationService;
		$authService->switchUserAccount($user, $token);

		if($deviceTokenId) {
			UserDevice::where('id', $deviceTokenId)
				->update([
					'user_id' => $user->id,
					'company_id' => $user->company_id,
				]);
		}
		return true;
    }

    /**
	 * update user credentials
	 * @param  User 	| $user		| Object of a user model
	 * @param  string 	| $email	| new email of user
	 * @param  text 	| $password | new password of user
	 * @return $user
	 */
	public function updateCredentials($user, $input)
	{
		if(ine($input, 'email')
			&& ine($input, 'new_password')
			&& $input['email'] == $user->email
			&& Hash::check($input['new_password'], $user->password)) {
                return $user;
        }

        $user = $this->repo->updateCredentials($user, $input);
        if($user->isOwner()) {
            $subscriptionService = App::make(SubscriptionServices::class);
            $subscriptionService->updateSubscriptionAccount($user);
        }

        return $user;
    }

    public function unassignDivision($user, $divisionId, $force=false)
    {
        if(!$force){
            $filters = [
                'division_ids' => $divisionId,
                'users' => $user->id
            ];

            $jobsQuery = $this->jobRepo->getJobsQueryBuilder($filters, ['customers']);
            $jobsCount = $jobsQuery->distinct('jobs.id')->count();

            if($jobsCount > 0){
                throw new UnassignDivisionException(trans('response.error.unassign_division', ['job_count' => $jobsCount]));
            }
        }
        DB::table('user_divisions')->where('user_id', $user->id)->where('division_id', $divisionId)->delete();

        return ;
	}

	/**
     * import users by group id
     * @param  Integer 	| $groupId 	| Id of a user group
     * @param  Object 	| $file 	| CSV file object
     * @return $userCounts
     */
    public function import($groupId, $file)
    {
    	$this->companyCountry = Country::whereCode(config('company_country_code'))->first();

    	switch ($groupId) {
    		case User::GROUP_SUB_CONTRACTOR_PRIME:
    			$userCounts = $this->savePrimeSubContractors($file);
    			break;
    		case User::GROUP_STANDARD_USER:
    			$userCounts = $this->saveStandardUsers($file);
    			break;
    		default:
    			$userCounts = [];
    			break;
    	}

    	return $userCounts;
    }

    public function changePassword($user, $oldPassword, $newPassword, $meta)
    {
    	$checkPassword = \Hash::check($oldPassword, $user->password);
    	if(!$checkPassword) {
    		throw new PasswordNotMatchException("Invalid old password.");
    	}

    	if($oldPassword == $newPassword) {
    		throw new NewPasswordShouldDifferentException("The new password should be different from old password.");

    	}

    	$this->repo->resetPassword($user, $newPassword);
    }

    /********** Private Functions **********/
	/**
	 * send invitation mail to a user
	 * @param  User 			$user
	 * @param  UserInvitataion	$invitation
	 * @return
	 */
	private function sendInvitationMail($user, $invitation, $company)
	{
		$inviter = Auth::user();
		$acceptanceUrl = route('users.accept.invitation', ['token' => $invitation->token]);
		$data = [
			'user'			 => $user,
			'inviter'		 => $inviter,
			'acceptance_url' => $acceptanceUrl,
			'company'		 => $company,
		];
		Mail::send('emails.users.new_company_invitation', $data, function($mail) use($user, $company) {
			$mail->to($user->email);
			$mail->subject("New invitation from {$company->name}");
		});
    }

    /**
	 * create new user if user accept an invitation
	 * @param  User 			$existingUser 	Parent user object
	 * @param  UserInvitation 	$invitation 	Invitation object
	 * @return $newUser
	 */
	private function createNewUser($existingUser, $invitation)
	{
		$data = [
			'first_name' => $existingUser->first_name,
			'last_name'	 => $existingUser->last_name,
			'email'		 => $existingUser->email,
			'group_id'	 => $invitation->group_id,
			'company_id' => $invitation->company_id,
			'company_name' => $existingUser->company_name,
		];
		$profileData = [
			'phone'				=> $existingUser->profile->phone,
			'cell'				=> $existingUser->profile->cell,
			'address'			=> $existingUser->profile->address,
			'address_line_1'	=> $existingUser->profile->address_line_1,
			'city'				=> $existingUser->profile->city,
			'state_id'			=> $existingUser->profile->state_id,
			'zip'				=> $existingUser->profile->zip,
			'country_id'		=> $existingUser->profile->country_id,
			'position'			=> $existingUser->profile->position,
			'additional_phone'	=> $existingUser->profile->additional_phone,
			'profile_pic'		=> $existingUser->profile->profile_pic,
		];

        $newUser = $this->repo->register($data, $profileData, $invitation->group_id, $invitation->company_id);
		$newUser->multiple_account = true;
		$newUser->save();

        $existingUser->multiple_account = true;
		$existingUser->save();

        DB::table('users')
			->where('id', $newUser->id)
			->update(['password' => $existingUser->password]);

            if($newUser->isSubContractor()) {
			$resourceId = $this->createResourceDir($newUser);
			$newUser->resource_id = $resourceId;
			$newUser->save();
		}

        if($newUser->isNonLoggable()) return $newUser;

        Event::fire('JobProgress.Users.Events.UserWasCreated', new UserWasCreated($newUser));

        return $newUser;
    }

    /**
	 * create resource directory for subcontractor
	 * @param  User $subContractor
	 * @return id of directory
	 */
    private function createResourceDir($subContractor)
	{
        $parentDirId = $this->getRootDir($subContractor);

		$dirName = $subContractor->first_name.'_'.$subContractor->last_name.'_'.$subContractor->id;
		$subContractorDir = $this->resourceService->createDir($dirName, $parentDirId);

        return $subContractorDir->id;
    }

    /**
	 * get root directory of sub contractor
	 * @return parent id
	 */
	private function getRootDir($subContractor)
	{
		$parentDir = Resource::name(Resource::LABOURS)->company($subContractor->company_id)->first();
		if(!$parentDir){
			$root = Resource::companyRoot($subContractor->company_id);
			$parentDir = $this->resourceService->createDir(Resource::LABOURS, $root->id);
		}
		return $parentDir->id;
	}

	/**
	 * save subcontractor prime users on file import
	 * @param  $file Excel file records
	 * @return user counts
	 */
	private function savePrimeSubContractors($file)
	{
		$records = Excel::load($file->getRealPath());
		$rules = [
			'first_name'		=>	'required',
			'last_name'			=>	'required',
			'email'				=>	'required|email|unique:users,email',
			'group_id'			=>	'required',
			'rating'			=>	'numeric|min:0|max:5',
			'address'    		=>	'required',
			'city'       		=>	'required',
			'state_id'   		=>	'required',
			'zip'        		=>	'required',
			'country_id' 		=>	'required',
			'additional_phone'	=>	'array',
		];

		$totalCounts = count($records->toArray());
		$userImported = 0;

		foreach ($records->toArray() as $key => $value) {

			if(!ine($value, 'email')) {
				continue;
			}

			$state = State::nameOrCode($value['state'])->first();
			if(!$state) {
				continue;
			}

			$value['group_id']		= User::GROUP_SUB_CONTRACTOR_PRIME;
			$value['state_id']		= $state->id;
			$value['country_id']	= $this->companyCountry->id;
			$value['company_id']	= getScopeId();
			$value['active']		= true;
			$value['password']		= ine($value, 'password') ? $value['password'] : '123456';

			$validator = Validator::make($value, $rules);
			if( $validator->fails()){
				continue;
			}

			$additionalPhones = [];
			DB::beginTransaction();

			try {
				if(ine($value, 'business_phone')) {
					$additionalPhones[] = [
						'label' => 'Phone',
						'phone' => $value['business_phone']
					];
				}
				if(ine($value, 'business_fax')) {
					$additionalPhones[] = [
						'label' => 'Fax',
						'phone' => $value['business_fax']
					];
				}
				if(ine($value, 'home_phone')) {
					$additionalPhones[] = [
						'label' => 'Home',
						'phone' => $value['home_phone']
					];
				}
				if(ine($value, 'mobile_phone')) {
					$additionalPhones[] = [
						'label' => 'Cell',
						'phone' => $value['mobile_phone']
					];
				}

				if($additionalPhones) {
					$value['additional_phone'] = $additionalPhones;
				}

				$value['stop_db_transaction'] = true;

				$subContractor = $this->execute(UserCreateCommand::class, ['input' => $value]);

				$role = Role::byName('sub-contractor-prime');
				$subContractor->roles()->detach();
				$subContractor->attachRole($role);

				$resourceId = $this->createResourceDir($subContractor);
				$subContractor->resource_id = $resourceId;
				$subContractor->save();
				$userImported++;
				Event::fire('JobProgress.SubContractors.Events.SubContractorGroupChanged', new SubContractorGroupChanged($subContractor));
			} catch (Exception $e) {
				DB::rollback();
				Log::error($e);
			}
			DB::commit();
		}

		return [
			'total_users' => $totalCounts,
			'user_imported' => $userImported,
		];
	}

	/**
	 * save standard users on importing a file
	 * @param  Object | $file | Excel File Object
	 * @return user counts
	 */
	private function saveStandardUsers($file)
	{
		$records = Excel::load($file->getRealPath());

		$rules = [
			'first_name'		=>	'required',
			'last_name'			=>	'required',
			'email'				=>	'required|email|unique:users,email',
			'group_id'			=>	'required',
			'address'    		=>	'required',
			'city'       		=>	'required',
			'state_id'   		=>	'required',
			'zip'        		=>	'required',
			'country_id' 		=>	'required',
			'additional_phone'	=>	'array',
		];

		$totalCounts = count($records->toArray());
		$userImported = 0;

		foreach ($records->toArray() as $key => $value) {
			if(!ine($value, 'email')) {
				continue;
			}

			$state = State::nameOrCode($value['state'])->first();
			if(!$state) {
				continue;
			}

			$value['group_id']		= User::GROUP_STANDARD_USER;
			$value['state_id']		= $state->id;
			$value['country_id']	= $this->companyCountry->id;
			$value['company_id']	= getScopeId();
			$value['active']		= true;
			$value['password']		= ine($value, 'password') ? $value['password'] : '123456';

			$validator = Validator::make($value, $rules);
			if( $validator->fails()){
				continue;
			}

			$additionalPhones = [];

			DB::beginTransaction();

			try {
				if(ine($value, 'business_phone')) {
					$additionalPhones[] = [
						'label' => 'Phone',
						'phone' => $value['business_phone']
					];
				}
				if(ine($value, 'business_fax')) {
					$additionalPhones[] = [
						'label' => 'Fax',
						'phone' => $value['business_fax']
					];
				}
				if(ine($value, 'home_phone')) {
					$additionalPhones[] = [
						'label' => 'Home',
						'phone' => $value['home_phone']
					];
				}
				if(ine($value, 'mobile_phone')) {
					$additionalPhones[] = [
						'label' => 'Cell',
						'phone' => $value['mobile_phone']
					];
				}

				if($additionalPhones) {
					$value['additional_phone'] = $additionalPhones;
				}

				$value['stop_db_transaction'] = true;

				$user = $this->execute(UserCreateCommand::class, ['input' => $value]);
				$userImported++;
			} catch (Exception $e) {
				DB::rollback();
				Log::error($e);
			}
			DB::commit();
		}

		return [
			'total_users' => $totalCounts,
			'user_imported' => $userImported,
		];
	}
 }