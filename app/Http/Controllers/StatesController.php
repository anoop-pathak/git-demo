<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\State;
use App\Transformers\CompanyStatesTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class StatesController extends ApiController
{

    public function __construct(Larasponse $response)
    {
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /states
     *
     * @return Response
     */
    public function index($countryId = null)
    {

        $input = Request::onlyLegacy('company_id');
        $states = [];
        if ($input['company_id']) {
            $company = Company::findOrFail($input['company_id']);
            $states = $company->states;

            $data = $this->response->collection($states, new CompanyStatesTransformer);

            $data['current_state_id'] = $company->office_state;

            return ApiResponse::success($data);
        } else {
            $states = $countryId ? State::where('country_id', $countryId)->get() : State::all();

            return ApiResponse::success(['data' => $states]);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /states
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name', 'country_id');

        $validator = Validator::make($inputs, State::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!State::create($inputs)) {
            return ApiResponse::errorInternal();
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'State'])
        ]);
    }

    /**
     * Display the specified resource.
     * GET /states/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $state = State::findOrFail($id);

        return ApiResponse::success(['data' => $state]);
    }
}
