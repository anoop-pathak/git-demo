<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use App\Models\UserProfile;
use App\Transformers\UserProfileTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /userprofile
     *
     * @return Response
     */
    protected $transformer;

    public function __construct(UserProfileTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Store a newly created resource in storage.+++
     * POST /userprofile
     *
     * @return Response
     */
    public function store($userId = null)
    {
        if ($userId) {
            return ApiResponse::errorNotFound(Lang::get('response.error.invalid', ['attribute' => 'Request']));
        }

        $user = User::find($userId);

        if (!$user) {
            return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'User']));
        }

        $inputs = Request::all();
        $inputs['user_id'] = $userId;
        $validator = Validator::make($inputs, UserProfile::getCreateRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!UserProfile::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'User Profile'])
        ]);
    }

    /**
     * Display the specified resource.
     * GET /userprofile/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($userId)
    {
        $user = User::findOrFail($userId);

        $user['profile'] = $user->profile;
        return ApiResponse::success(['data' => $this->transformer->transform($user)]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /userprofile/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($userId)
    {
        $user = User::findOrFail($userId);
        $inputs = Request::all();

        $validator = Validator::make($inputs, UserProfile::getUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $user->first_name = $inputs['first_name'];
        $user->last_name = $inputs['last_name'];
        $user->update();
        $user->profile->update($inputs);
        return ApiResponse::success([
            'message' => Lang::get('response.success.updated', ['attribute' => 'User'])
        ]);
    }
}
