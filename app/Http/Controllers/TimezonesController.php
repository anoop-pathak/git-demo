<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Timezone;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class TimezonesController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /timezones
     *
     * @return Response
     */
    public function index($countryId = null)
    {
        $timezones = $countryId ? Timezone::where('country_id', $countryId)->get() : Timezone::all();
        return ApiResponse::success(['data' => $timezones]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /timezones
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::all();

        $validator = Validator::make($inputs, Timezone::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Timezone::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Time-zone']),
        ]);
    }
}
