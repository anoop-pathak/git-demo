<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\FinancialProduct;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSignature;
use App\Repositories\UserRepository;
use App\Services\Contexts\Context;
use FlySystem;
use Firebase;
use App\Transformers\Optimized\UsersTransformer as UsersOptimizedTransformer;
use App\Transformers\UserFormTransformer;
use App\Transformers\UsersExportTransformer;
use App\Transformers\UsersListWithCountsTransformer;
use App\Transformers\UsersTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Excel;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\UnassignDivisionException;
use App\Services\Users\UserService;
use App\Exceptions\InvalidDivisionException;

use App\Transformers\UserInvitationsTransformer;
use App\Transformers\CompaniesTransformer;
use App\Exceptions\AccessTokenNotFoundException;
use App\Exceptions\UpdateUserNotAllowedException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InactiveAccountException;
use App\Exceptions\TerminatedAccountException;
use App\Exceptions\SuspendedAccountException;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use App\Transformers\UsersSelectListTransformer;
use App\Repositories\SubscribersRepository;
use App\Exceptions\PasswordNotMatchException;
use App\Exceptions\NewPasswordShouldDifferentException;
use App\Transformers\CrewTrackingUserTransformer;
use App\Events\UpdateAnonymousUser;
use App\Events\ReleaseTwilioNumberForSingleUser;
use App\Models\Tag;
use Event;

// use User;

class UsersController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /users
     *
     * @return Response
     */
    protected $response;
    protected $user;
    protected $repo;
    protected $scope;
    protected $service;

    public function __construct(User $user, Larasponse $response, UserRepository $repo, Context $scope, UserService $service, SubscribersRepository $subscriberRepo)
    {
        parent::__construct();
        $this->service = $service;
        $this->scope = $scope;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        $this->user = $user;
        $this->repo = $repo;
        $this->subscriberRepo = $subscriberRepo;

        $this->middleware('company_scope.ensure', ['only' => ['index', 'store', 'show']]);
    }

    /**
     *  get company users list according to company wise.
     *
     * @access public
     * @return json of company users listing.
     */
    public function index()
    {

        $input = Request::all();
        try{
            $users = $this->repo->getFilteredUsers($input);

            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $users    = $users->get();
                $response = $this->response->collection($users, new UsersTransformer);
            } else {
                $users    = $users->paginate($limit);
                $response =  $this->response->paginatedCollection($users, new UsersTransformer);
            }

            if(\Auth::user()->isSubContractorPrime() && !ine($input, 'exclude_sub_user')) {
                $response['data'] = $this->addCurrentUserInResponse($response['data']);
            }

            return ApiResponse::success($response);
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * Store a newly created resource in storage.
     * POST /users
     *
     * @return Response
     */
    public function store()
    {

        $input = Request::all();

        $rules = array_merge(User::getCreateRules(), UserProfile::getCreateRules());
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $input['company_id'] = $this->scope->id();
            $user = $this->executeCommand('\App\Commands\UserCreateCommand', $input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.registration'),
                'user' => $this->response->item($user, new UsersTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function add_standard_user()
    {
        $input = Request::all();
        $input['group_id'] = User::GROUP_STANDARD_USER;
        $input['admin_privilege'] = 0;
        $input['departments'] = null;

        $rules = array_merge(User::getCreateRules(), UserProfile::getCreateRules());
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        if(ine($input,'tag_ids')) {
            $tags = Tag::where('company_id', getScopeId())
                ->where('type', Tag::TYPE_USER)
                ->whereIn('id', $input['tag_ids'])
                ->count();

            if($tags != count(arry_fu($input['tag_ids']))) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'Tag id(s)']));
            }
        }

        try {

            $input['company_id'] = $this->scope->id();
            $user = $this->executeCommand('\App\Commands\UserCreateCommand', $input);

            if(ine($input,'tag_ids')) {
				$this->repo->assignTags($user, $input['tag_ids']);
			}
            return ApiResponse::success([
                'message' => Lang::get('response.success.registration'),
                'user' => $this->response->item($user, new UsersTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /users/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $user = $this->repo->getById($id);
        return ApiResponse::success(['data' => $this->response->item($user, new UsersTransformer)]);
    }

    /**
     * Edit the specified  resource in storage.
     * get /users/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $user = $this->repo->getById($id);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }
        return ApiResponse::success(['data' => $this->response->item($user, new UserFormTransformer)]);
    }

    /**
     * Update the specified  resource in storage.
     * PUT /users/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $user = $this->repo->getById($id);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }
        $input = Request::all();

        $rules = array_merge(User::getUpdateRules($id), UserProfile::getUpdateRules());
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if(ine($input, 'email')) {
			$emailExists = User::where('email', $input['email'])
				->where('email', '<>', $user->email)
                ->exists();

			if($emailExists) {
				return ApiResponse::errorGeneral(trans('response.error.duplicate_email'));
			}
        }

        $input['id'] = $id;

        try {
            $user = $this->executeCommand('\App\Commands\UserUpdateCommand', $input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'User']),
                'user' => $this->response->item($user, new UsersTransformer)
            ]);
        } catch(UpdateUserNotAllowedException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function upload_image()
    {

        $input = Request::onlyLegacy('user_id', 'image');

        $validator = Validator::make($input, User::getUploadProPicRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $user = $this->repo->getById($input['user_id']);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }

        $this->deleteProfilePic($user);
        $profilePic = $this->uploadProfilePic($input);
        $user->profile->profile_pic = $profilePic;

        if ($user->profile->save()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => ' Profile picture']),
                'data' => [
                    'profile_pic' => FlySystem::publicUrl(config('jp.BASE_PATH') . $profilePic),
                ]
            ]);
        }

        return ApiResponse::errorInternal();
    }

    public function delete_profile_pic()
    {
        $input = Request::onlyLegacy('user_id');
        $validator = Validator::make($input, ['user_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $user = $this->repo->getById($input['user_id']);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }
        try {
            $this->deleteProfilePic($user);
            $user->profile->profile_pic = null;
            $user->profile->save();
            return ApiResponse::success(['message' => Lang::get('response.success.profile_pic_removed')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function active()
    {
        $input = Request::onlyLegacy('user_id', 'active_status');
        $validator = Validator::make($input, ['user_id' => 'required', 'active_status' => 'required|boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $user = $this->repo->getById($input['user_id']);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }
        try {
            if ($input['active_status']) {
                $this->repo->activate($user);
            } else {
                $this->repo->deactivate($user);
                Event::fire('JobProgress.Twilio.Events.ReleaseTwilioNumberForSingleUser', new ReleaseTwilioNumberForSingleUser($user));
            }
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }

        if ($input['active_status'] == true) {
            $status = 'activated';
        } else {
            $status = 'deactivated';
        }
        return ApiResponse::success(['message' => 'User ' . $status . ' successfully']);
    }


    /**
     * Get Users list with customer and jobs counts (As representative)
     * Get /users/with_count
     *
     * @return Response
     */
    public function getUsersWithCustomersJobsCount()
    {
        try{
            $users = $this->repo->getUsers($sortabe = false, $withJobCountsAsCR = false)->active();
            $users = $users->with('customers', 'jobsAsEstimator', 'jobsAsRep')->get();

            // users with jobs counts..
            $data['users'] = $this->response->collection($users, new UsersListWithCountsTransformer)['data'];

            // usassigned jobs counts..
            $jobRepo = App::make(\App\Repositories\JobsListingRepository::class);
            $customerRepo = App::make(\App\Repositories\CustomerListingRepository::class);

            $data['unassigned_count']['cr'] = $customerRepo->getFilteredCustomersCount(['rep_ids' => 'unassigned']);
            $data['unassigned_count']['jr'] = $jobRepo->getFilteredJobsCount(['job_rep_ids' => 'unassigned']);

            foreach ($users as $key => $user) {
                $data['users'][$key]['job_count_as_jr'] = $jobRepo->getFilteredJobsCount(['job_rep_ids' => $user->id]);
            }

            return ApiResponse::success([
                'data' => $data
            ]);
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * Get Users Task and Appoitment counts for today.
     * Get /users/daily_plan_count
     *
     * @return Response
     */
    public function daily_plan_count()
    {
        $userId = \Auth::id();
        $tasks = Task::today()->pending()->assignedTo($userId)->count();
        $appointments = Appointment::recurring()->today()->current()->count();
        $count = $tasks + $appointments;
        return ApiResponse::success([
            'count' => $count
        ]);
    }

    /**
     * Update Group of a specific user
     * PUT /users/{id}/group
     *
     * @param  int $id
     * @return Response
     */
    public function update_group($id)
    {
        $user = $this->repo->getById($id);
        if (!SecurityCheck::AccessOwner($user)) {
            return SecurityCheck::$error;
        }

        $input = Request::onlyLegacy('group_id');
        $validator = Validator::make($input, User::getChangeGroupRules(), User::customMessagesChangeGroup());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            // check if role changed fro owner..
            $changedOwner = null;
            if ($input['group_id'] == User::GROUP_OWNER) {
                $company = $user->company;
                $changedOwner = $company->subscriber;
            }

            $user->group_id = $input['group_id'];
            $user->save();
            $this->repo->assignRole($user);

            // change previous owner to admin..
            if ($changedOwner) {
                $changedOwner->group_id = User::GROUP_ADMIN;
                $changedOwner->save();
                $this->repo->assignRole($changedOwner);
            }

            if($input['group_id'] == User::GROUP_OWNER){
				Event::fire('JobProgress.Users.Events.UpdateAnonymousUser', new UpdateAnonymousUser($user));
			}

            // update firebase for new roles..
            Firebase::updateUserSettings($user);
            Firebase::updateWorkflow();
            Firebase::updateUserPermissions($user->id);

            if ($changedOwner) {
                Firebase::updateUserSettings($user);
                Firebase::updateWorkflow();
                Firebase::updateUserPermissions($user->id);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => Lang::get('response.success.updated', ['attribute' => 'User']),
            'user' => $this->response->item($user, new UsersTransformer)
        ]);
    }

    /**
     * Get system user rule
     * Get /user/get_system_user
     * @param  string password
     * @param  id     company_id
     * @return Response
     */
    public function get_system_user()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }
        $input = Request::onlyLegacy('company_id', 'password');
        $validator = Validator::make($input, User::getSystemUserRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }
        $company = Company::findOrFail($input['company_id']);
        try {
            $firstName = strtoupper(substr(clean($company->name), 0, 1));
            $middleName = strtolower(clean($company->subscriber->last_name));
            $lastName = $company->id . '@jobprogress.com';
            $systemUser['username'] = $firstName . $middleName . $lastName;
            $systemUser['password'] = 'JP' . strtolower(strrev(str_replace(' ', '', $company->subscriber->last_name)));
            return ApiResponse::success([
                'system_user' => $systemUser
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function get_demo_user()
    {
        $demoUser = User::standard()->demoUser()->active()->orderByRaw("RAND()")->firstOrFail();
        $data['username'] = $demoUser->email;
        $data['password'] = config('jp.demo_pass');
        return ApiResponse::success([
            'data' => $data
        ]);
    }

    /**
     * Get company/users/all
     * @return [array] [user listing]
     */
    public function all()
    {
        try {
            $users = User::whereCompanyId($this->scope->id())
                ->notHiddenUser()
                ->get();

            return ApiResponse::success($this->response->collection(
                $users,
                new UsersOptimizedTransformer
            ));
        } catch(InvalidDivisionException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Create or update user signature
     * POST /users/signature
     *
     * @return Response
     */
    public function createOrUpdateSignature()
    {
        $input = Request::onlyLegacy('signature', 'user_id');
        $validator = Validator::make($input, ['signature' => 'required', 'user_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $userSignature = UserSignature::firstOrCreate(['user_id' => $input['user_id']]);
            $userSignature->signature = $input['signature'];
            $userSignature->save();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'User signature'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get signatures
     * GET /users/signature
     *
     * @return Response
     */
    public function getSignatures()
    {
        $input = Request::onlyLegacy('user_ids');
        $validator = Validator::make($input, ['user_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $signatures = UserSignature::whereIn('user_id', $input['user_ids'])
            ->get(['user_id', 'signature']);

        return ApiResponse::success(['data' => $signatures]);
    }

    /**
     * Get email/verify
     * @return Response
     */
    public function emailVerify()
    {
        $input = Request::onlyLegacy('email');
        $validator = Validator::make($input, ['email' => 'email|required']);
        if ($validator->fails()) {
            $message = $validator->messages();

            return ApiResponse::errorGeneral($message->first());
        }

        $user = User::whereEmail($input['email'])->Loggable()->first();
        if ($user) {
            return ApiResponse::errorGeneral('The email already exists.');
        }

        return ApiResponse::success([]);
    }

    /**
     * Get company/users/export
     * @return .csv file
     */
    public function export()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::all();
        $subscribers = $this->subscriberRepo->getFilteredSubscribers($input)
            ->select('companies.id');

        $companyQuery = generateQueryWithBindings($subscribers);

        $users = $this->repo->getFilteredUsers($input);

        $users->join(DB::raw("({$companyQuery}) as companies"), 'companies.id', '=', 'users.company_id')
            ->select('users.*');
        $users = $users->with('profile.state', 'company.country', 'group')
            ->companyUsers()
            ->get();

        $users = $this->response->collection($users, new UsersExportTransformer);

        Excel::create('Users', function ($excel) use ($users) {
            $excel->sheet('sheet1', function ($sheet) use ($users) {
                $sheet->fromArray($users['data']);
            });
        })->export('csv');
    }

    /**
     * saveUserCommission
     * Put /company/user/{id}/save_commission
     *
     * @param  int $id
     * @return Response
     */
    public function saveUserCommission($id)
    {
        $input = Request::onlyLegacy('commission_percentage');
        $validator = Validator::make($input, ['commission_percentage' => 'required|numeric']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        try {
            $user = $this->repo->getById($id);
            $user->commission_percentage = $input['commission_percentage'];
            $user->save();
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Commission Percentage']),
        ]);
    }

    /**
     * @ Assign/UnAssign user division
     */
    public function assignDivision($id)
    {
        $input = Request::all();
        $validator = Validator::make($input, []);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $divisionIds = ine($input, 'division_ids') ? $input['division_ids']: [];
        $allDivisonAccess = ine($input, 'all_divisions_access');
        $user = $this->repo->getById($id);
        try {
            $this->repo->assignDivisions($user, $divisionIds, $allDivisonAccess);
            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Division(s)']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function unassignDivision($id){
    	$input = Request::all();
    	$validator = Validator::make($input, ['division_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $user = $this->repo->getById($id);
        try{
        	$force = ine($input, 'force');
        	$this->service->unassignDivision($user, $input['division_id'], $force);
        	return ApiResponse::success([
                'message' => trans('response.success.unassign_division', ['attribute' => 'User division']),
            ]);
        } catch(UnassignDivisionException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function updateColor($id)
    {
        $input = Request::onlyLegacy('color');
        $validator = Validator::make($input, ['color' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        try {
            $user = $this->repo->getById($id);
            $user->color = $input['color'];
            $user->save();
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Color']),
        ]);
    }

    /**
	 * send invitation to user to join a new company
	 *
	 * POST - /users/send_invitation
	 *
	 * @return response
	 */
	public function sendInvitation()
	{
		if(!Auth::user()->isAuthority() && !Auth::user()->isSuperAdmin()) {
			return ApiResponse::errorForbidden();
		}
		$input = Request::all();
		$groups = [User::GROUP_STANDARD_USER,User::GROUP_SUB_CONTRACTOR,];
		$validator = Validator::make($input, [
			'email'		=> 'required',
			'group_id'	=> 'required|in:'.implode(',', $groups),
		]);
		if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		$company = Company::findOrFail($this->scope->id());
		$alreadyExist = $this->user->where('email', $input['email'])
			->where('company_id', $this->scope->id())
			->exists();
		if($alreadyExist) {
			return ApiResponse::errorGeneral(trans('response.error.already_exist', ['attribute' => 'User']));
		}
		$user = $this->user->where('email', $input['email'])
			->firstOrFail();
        if(!ine($input, 'force')) {
            $inDraft = $user->invitations()
                ->where('company_id', $this->scope->id())
                ->where('status', UserInvitation::DRAFT)
                ->excludeExpired()
                ->exists();

            if($inDraft) {
                return ApiResponse::errorGeneral(trans('response.error.invitation_already_sent'));
            }
        }
		try {
			DB::beginTransaction();
			$invitation = $this->service->sendInvitation($user, $company, $input['group_id']);
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.sent',['attribute' => 'Invitation']),
			]);
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
	 * invitation response send by user
	 *
	 * GET - /users/invitation_response/{token}
	 *
	 * @param  Int $id 	Id of Invitation
	 * @return response
	 */
	public function acceptInvitation($token)
	{
		$invitation = $this->service->getInvitationByToken($token);
		$createdAt  = Carbon::parse($invitation->created_at)->toDateString();
		$expireDate = Carbon::now()->subDays(config('jp.user_invitation_token_expire_limit'))->toDateString();
		$company = Company::findOrFail($invitation->company_id);
		if($createdAt < $expireDate) {
			return Response::view('invitations.users.expired');
		}
		if($invitation->status != UserInvitation::DRAFT) {
			return Response::view('invitations.users.accept', [
				'company' => $company
			]);
		}
		$existingUser = User::where('email', $invitation->email)
			->where('company_id', $invitation->company_id)
			->exists();
		if($existingUser) {
			return Response::view('invitations.users.accept', [
				'company' => $company
			]);
		}
		DB::beginTransaction();
		try {
			$invitation = $this->service->acceptInvitation($invitation);
			DB::commit();
			return Response::view('invitations.users.accept', [
				'company' => $company
			]);
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
	 * get invitation list of a user
	 *
	 * GET - /users/invitations
	 *
	 * @return response
	 */
	public function invitationList()
	{
		$input = Request::all();
		$invitationList = $this->service->invitationList();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		if(!$limit) {
			$invitationList = $invitationList->get();
			$response = $this->response->collection($invitationList, new UserInvitationsTransformer);
		} else {
			$invitationList = $invitationList->paginate($limit);
			$response =  $this->response->paginatedCollection($invitationList, new UserInvitationsTransformer);
		}
		return ApiResponse::success($response);
    }

    /**
	 * get all companies of a user
	 *
	 * GET - /users/company_list
	 *
	 * @return response
	 */
	public function companyList()
	{
		$input = Request::all();
		$companies = $this->service->companyList();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		if(!$limit) {
			$companies = $companies->get();
			$response  = $this->response->collection($companies, new CompaniesTransformer);
		} else {
			$companies = $companies->paginate($limit);
			$response  =  $this->response->paginatedCollection($companies, new CompaniesTransformer);
		}
		return ApiResponse::success($response);
    }

    /**
	 * switch user to another company
	 *
	 * POST - /users/switch_company
	 *
	 * @return [type] [description]
	 */
	public function switchCompany()
	{
		$input = Request::all();

        $validator = Validator::make($input, [
			'company_id' => 'required',
		]);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
        }

		if(getScopeId() == $input['company_id']) {
			return ApiResponse::errorGeneral(trans('response.error.already_in_same_company'));
		}
		$user = User::where('email', Auth::user()->email)
			->where('company_id', $input['company_id'])
            ->firstOrFail();

        $deviceTokenId = ine($input, 'device_token_id') ? $input['device_token_id'] : null;

		DB::beginTransaction();
		try {
            $this->service->switchAccount($user, Request::header('Authorization'), $deviceTokenId);

			setScopeId($user->company_id);
            DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.company_switched'),
				'data'	  => $this->response->item($user, new UsersTransformer),
				'is_restricted' => SecurityCheck::RestrictedWorkflow($user),
			]);
		} catch (InActiveUserException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (InactiveAccountException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (TerminatedAccountException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (SuspendedAccountException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (AccessTokenNotFoundException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
	 * verify user email for invitation
	 *
	 * GET - /users/verify_email
	 *
	 * @return response
	 */
	public function verifyUserEmail()
	{
		$input = Request::all();
		$validator = Validator::make( $input, ['email' => 'email|required']);
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		$user = User::where('email', $input['email'])->first();
		return ApiResponse::success([
			'user_exists' => (bool)$user
		]);
    }

    /**
	 * update user credentials
	 *
	 * PUT - /users/{id}/update_credentials
	 *
	 * @param  Int 		| $id 	| ID of a user
	 * @return response
	 */
	public function updateCredentials($id)
	{
		if(Auth::id() != $id) {
			return ApiResponse::errorGeneral(trans('response.error.cannot_update', ['attribute' => 'user']));
		}

        $user = $this->repo->getById($id);
		$input = Request::all();
		$validator = Validator::make($input, User::updateCredentialsRules());

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        if (!Hash::check($input['old_password'], $user->password)) {
			return ApiResponse::errorGeneral(trans('response.error.incorrect_password'));
		}
		$checkEmail = false;

        if(ine($input, 'email') && ($user->email != $input['email'])) {
			$checkEmail = User::where('email', $input['email'])->exists();
		}

        if($checkEmail) {
			return ApiResponse::errorGeneral(trans('response.error.duplicate_email'));
        }

		DB::beginTransaction();
		try {
			$user = $this->service->updateCredentials($user, $input);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'User']),
				'data'	  => $this->response->item($user, new UsersTransformer),
			]);
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

   	/**
	 * assign tags
	 * PUT - /company/user/{id}/assign_tags
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function assignTags($id)
	{
		$users = $this->repo->getById($id);
		$input = Request::all();
        $validator = Validator::make($input, ['tag_ids' => 'required|array']);

		if ($validator->fails()) {
			return ApiResponse::validation($validator);
        }

		try{
			$this->repo->assignTags($users, $input['tag_ids']);

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'User group(s)']),
			]);
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
	 * import users
	 *
	 * POST - /users/import
	 *
	 * @return response
	 */
	public function import()
	{
		$input = Request::all();
		$validator = Validator::make($input, User::getImportRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$userCounts = $this->service->import($input['group_id'], $input['file']);

		return ApiResponse::success([
			'message' => "{$userCounts['user_imported']} out of {$userCounts['total_users']} user(s) imported successfully.",
		]);
	}

	public function changePassword()
	{
		$input = Request::all();
		$validator = Validator::make($input, User::getResetPasswordRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$user = Auth::user();
			$response = $this->service->changePassword($user, $input['old_password'], $input['new_password'], $input);

			return ApiResponse::success([
				'message'	=> trans('response.success.changed', ['attribute' => 'Password'])
			]);
		} catch(PasswordNotMatchException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(NewPasswordShouldDifferentException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		}
		catch(\Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function getCrewTrackingUsers()
	{
		$input = Request::all();

		$users = $this->repo->getFilteredUsers($input);
		return $this->response->collection($users->get(), new CrewTrackingUserTransformer);
	}

	public function selectList()
	{
		$input = Request::all();
		try{
			$users = $this->repo->getUsersQueryBuilder($input);

			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {
				$users 	  = $users->get();
				$response = $this->response->collection($users, new UsersSelectListTransformer);
			} else {
				$users 	  = $users->paginate($limit);
				$response =  $this->response->paginatedCollection($users, new UsersSelectListTransformer);
			}

			if(Auth::user()->isSubContractorPrime() && !ine($input, 'exclude_sub_user')) {
				$response['data'] = $this->addCurrentUserInResponseSelectList($response['data']);
			}

			return ApiResponse::success($response);
		} catch(InvalidDivisionException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);

		}
	}

    /********************* Private Function ***********************/
    private function uploadProfilePic($data)
    {
        $filename = $data['user_id'] . '_' . Carbon::now()->timestamp . '.jpg';
        $baseName = 'company/users/' . $filename;
        $fullpath = config('jp.BASE_PATH') . $baseName;

        // \Image::make($data['image'])->resize(200, 200, function($constraint) {
        //     $constraint->upsize();
        // })->save($fullpath);

        $image = \Image::make($data['image']);
        if ($image->height() > $image->width()) {
            $image->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }
        FlySystem::put($fullpath, $image->encode()->getEncoded());

        return $baseName;
    }

    private function deleteProfilePic($user)
    {
        $profilePic = $user->profile->profile_pic;
        if (empty($profilePic)) {
            return false;
        }
        $fullpath = config('jp.BASE_PATH') . $profilePic;
        FlySystem::delete($fullpath);
    }

    private function addCurrentUserInResponse($userList)
    {
        if(in_array(Auth::id(), array_column($userList, 'id'))) {
            return $userList;
        }
        $userList[] = $this->response->item(Auth::user(), new UsersTransformer);
        array_multisort(array_column($userList, 'id'), SORT_ASC, $userList);
        return $userList;
    }

    private function addCurrentUserInResponseSelectList($userList)
	{
		if(in_array(Auth::id(), array_column($userList, 'id')))	{

			return $userList;
		}

		$userList[] = $this->response->item(Auth::user(), new UsersSelectListTransformer);

		array_multisort(array_column($userList, 'id'), SORT_ASC, $userList);

		return $userList;
	}
}
