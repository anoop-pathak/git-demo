<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\Country;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;

class CountriesController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /countries
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::onlyLegacy('company_id');
        $countries = [];
        if ($input['company_id']) {
            $company = Company::findOrFail($input['company_id']);
            $countryId = ($company->company_country) ?: $company->office_country;
            $companyCountry = Country::find($countryId);
            $companyCountry->phone_format = config("jp.country_phone_masks.{$companyCountry->code}");
            $countries[] = $companyCountry;
        } else {
            $countries = Country::all();
            $countries->each(function ($country) {
                $country->phone_format = config("jp.country_phone_masks.{$country->code}");
            });
        }


        return ApiResponse::success(['data' => $countries]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /countries
     *
     * @return Response
     */
    public function store()
    {
        $inputs = Request::onlyLegacy('name');

        $validator = Validator::make($inputs, Country::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!Country::create($inputs)) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'));
        }

        return ApiResponse::success([
            'message' => Lang::get('response.success.saved', ['attribute' => 'Country'])
        ]);
    }

    /**
     * Display the specified resource.
     * GET /countries/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $country = Country::findOrFail($id);
        return ApiResponse::success(['data' => $country]);
    }
}
