<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Trade;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class TradesController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /trades
     *
     * @return Response
     */
    public function index()
    {
        switchDBToReadOnly();

        $trades = Trade::orderby('name')->get();

        switchDBToReadWrite();

        return ApiResponse::success(['data' => $trades]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /trades
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name');

        $validator = Validator::make($inputs, Trade::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Trade::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Trade'])
        ]);
    }
}
