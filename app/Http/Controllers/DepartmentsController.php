<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Department;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class DepartmentsController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /departments
     *
     * @return Response
     */
    public function index()
    {
        $departments = Department::all();

        return ApiResponse::success(['data' => $departments]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /Departments
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name');

        $validator = Validator::make($inputs, Department::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Department::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Department'])
        ]);
    }
}
