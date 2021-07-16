<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Permission;
use App\Models\UserPermission;
use App\Repositories\UserRepository;
use Firebase;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class PermissionsController extends ApiController
{

    protected $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
        parent::__construct();
    }

    public function index()
    {
        if (Request::get('all')) {
            $permissions = Permission::pluck('name')->toArray();
        } elseif ($userId = Request::get('user_id')) {
            $user = $this->userRepo->getById($userId);
            $permissions = $user->listPermissions();
        } else {
            $user = \Auth::user();
            $permissions = $user->listPermissions();
        }
        return ApiResponse::success([
            'data' => $permissions
        ]);
    }

    public function assignPermissions()
    {
        $input = Request::onlyLegacy('user_id', 'allow', 'deny');
        $validator = Validator::make($input, Permission::getAssignPermissionRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $user = $this->userRepo->getById($input['user_id']);
        $user->permissions()->delete();

        $allow = array_filter((array)$input['allow']);
        $deny = array_filter((array)$input['deny']);
        $deny = array_diff($deny, $allow);

        $permissions = [];
        foreach ($allow as $permission) {
            $permissions[] = [
                'permission' => $permission,
                'user_id' => $user->id,
                'allow' => true,
            ];
        }


        foreach ($deny as $permission) {
            $permissions[] = [
                'permission' => $permission,
                'user_id' => $user->id,
                'allow' => false,
            ];
        }

        if (!empty($permissions)) {
            // dd($permissions);
            UserPermission::insert($permissions);
        }

        Firebase::updateUserPermissions($user->id);

        return ApiResponse::success([
            'message' => trans('response.success.permissions_assigned'),
        ]);
    }

    public function userLevelPermissions()
    {
        $input = Request::onlyLegacy('role_id');
		$userRolePermissions = config('user-role-permissions');

		$permissions = array_get($userRolePermissions, User::GROUP_STANDARD_USER);

		if(ine($input, 'role_id') && !Auth::user()->isStandardUser()) {
			$permissions = array_get($userRolePermissions, $input['role_id']);
		}

        return ApiResponse::success(['data' => $permissions]);
    }
}
