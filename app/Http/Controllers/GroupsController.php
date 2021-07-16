<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Group;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class GroupsController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /groups
     *
     * @return Response
     */
    public function index()
    {
        $groups = Group::all();

        return ApiResponse::success(['data' => $groups]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /groups
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name');

        $validator = Validator::make($inputs, Group::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Group::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Group'])
        ]);
    }

    /**
     * Display the specified resource.
     * GET /groups/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $group = Group::findOrFail($id);

        return $this->respond(['data' => $group]);
    }
}
