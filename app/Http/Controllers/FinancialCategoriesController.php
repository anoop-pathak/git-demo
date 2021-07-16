<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\FinancialCategory;
use App\Repositories\FinancialRepository;
use App\Transformers\FinancialCategoriesTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Events\FinancialCategoryCreated;
use App\Events\FinancialCategoryUpdated;
use App\Events\FinancialCategoryDeleted;
use Illuminate\Support\Facades\Event;

class FinancialCategoriesController extends ApiController
{

    protected $repo;
    protected $response;

    public function __construct(FinancialRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        parent::__construct();
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /financial_categories
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $categories = $this->repo->getCategories($input);

        return ApiResponse::success($this->response->collection($categories, new FinancialCategoriesTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /financial_categories
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('name', 'default');
        $validator = Validator::make($input, FinancialCategory::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $input['default'] = ine($input, 'default') ? true : false;
            $category = $this->repo->saveCategory($input['name'], $input['default']);
            Event::fire('JobProgress.Events.FinancialCategoryCreated', new FinancialCategoryCreated(['category' => $category]));
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Category']),
                'data' => $this->response->item($category, new FinancialCategoriesTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT /financial_categories/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $category = FinancialCategory::findOrFail($id);
        $input = Request::onlyLegacy('name', 'default');
        $validator = Validator::make($input, FinancialCategory::getRules($id));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($category->update($input)) {
            Event::fire('JobProgress.Events.FinancialCategoryUpdated', new FinancialCategoryUpdated(['category' => $category]));
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Category']),
                'data' => $this->response->item($category, new FinancialCategoriesTransformer),
            ]);
        }

        return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /financial_categories/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $category = FinancialCategory::findOrFail($id);

        if ($category->details->count()) {
            return ApiResponse::errorgeneral(Lang::get('response.error.delete_category_first_delete_details'));
        }

        if ($category->locked) {
            return ApiResponse::errorgeneral(trans('response.error.prevent_labor_category'));
        }

        DB::beginTransaction();
        try {
            // delete products with category..
            $category->products()->delete();
            $category->delete();
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal();
        }
        DB::commit();

        Event::fire('JobProgress.Events.FinancialCategoryDeleted', new FinancialCategoryDeleted(['category' => $category]));

        return ApiResponse::success([
            'message' => Lang::get('response.success.deleted', ['attribute' => 'FinancialCategory']),
        ]);
    }
}
