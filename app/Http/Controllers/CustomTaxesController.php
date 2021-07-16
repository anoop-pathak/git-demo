<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\CustomTax;
use App\Services\Contexts\Context;
use App\Transformers\CustomTaxesTransformer;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\CustomTaxRepository;

class CustomTaxesController extends ApiController
{

    public function __construct(Larasponse $response, Context $scope, CustomTaxRepository $repo)
    {
        $this->response = $response;
        $this->scope = $scope;
        $this->repo = $repo;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * Get /custom_tax/list
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $taxes = $this->repo->getListing($input);

        if (!$limit) {
            $taxes = $taxes->get();

            return ApiResponse::success($this->response->collection($taxes, new CustomTaxesTransformer));
        }
        $taxes = $taxes->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($taxes, new CustomTaxesTransformer));
    }


    /**
     * Store the specified resource.
     * Post /custom_tax
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('title', 'tax_rate');
        $validator = Validator::make($input, ['title' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $input['company_id'] = $this->scope->id();
            $input['created_by'] = \Auth::id();
            $tax = CustomTax::create($input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Tax']),
                'custom_tax' => $this->response->item($tax, new CustomTaxesTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Show the specified resource.
     * Post /custom_tax/{tax_id}
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $tax = CustomTax::whereCompanyId($this->scope->id())->whereId($id)->firstOrFail();

        return ApiResponse::success($this->response->item($tax, new CustomTaxesTransformer));
    }

    /**
     * Update the specified resource in storage.
     * Put /custom_tax/tax_id
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $tax = CustomTax::whereCompanyId($this->scope->id())->whereId($id)->firstOrFail();
        $input = Request::onlyLegacy('title', 'tax_rate');
        $validator = Validator::make($input, ['title' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $tax->title = $input['title'];
            $tax->tax_rate = $input['tax_rate'];
            $tax->save();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Tax']),
                'custom_tax' => $this->response->item($tax, new CustomTaxesTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Remove the specified resource from storage.
     * Delete /custom_tax/{tax_id}
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $tax = CustomTax::whereCompanyId($this->scope->id())
            ->whereId($id)
            ->firstOrFail();
        try {
            $tax->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Tax'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
