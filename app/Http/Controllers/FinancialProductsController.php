<?php
namespace App\Http\Controllers;

use App\Exceptions\InvalidImportTemplate;
use App\Models\ApiResponse;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\User;
use App\Repositories\FinancialProductsRepository;
use App\Repositories\SupplierRepository;
use App\Services\Contexts\Context;
use App\Services\FinancialProducts\ImportProducts;
use App\Transformers\FinancialProductsTransformer;
use App\Transformers\SubContractorRateSheetTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Services\FinancialProducts\FinancialProductService;
use App\Models\Supplier;
use Exception;

class FinancialProductsController extends Controller
{

    protected $repo;
    protected $response;
    protected $scope;
    protected $supplierRepo;
    protected $service;

    public function __construct(FinancialProductsRepository $repo, Larasponse $response, Context $scope, SupplierRepository $supplierRepo, FinancialProductService $service)
    {
        $this->scope = $scope;
        $this->repo = $repo;
        $this->response = $response;
        $this->supplierRepo = $supplierRepo;
        $this->service = $service;
        parent::__construct();
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the resource.
     * GET /financialproducts
     *
     * @return Response
     */
    public function index()
    {
        if(Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => Auth::id()]);
        }

        $input = Request::all();
        $products = $this->repo->getProducts($input);

        // // exclude srs products for mobile app
        // if (config('srs_disabled_for_mobile') && ($supplier = Supplier::srsSupplier())) {
        // 	$products = $products->where('supplier_id', '<>', $supplier->id);
        // }

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($products->get(), new FinancialProductsTransformer));
        }

        $products = $products->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($products, new FinancialProductsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /financialproducts
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('category_id', 'name', 'unit', 'unit_cost', 'code', 'description', 'selling_price', 'supplier_id', 'abc_additional_data', 'styles', 'sizes', 'colors', 'sub_id', 'qbd_id', 'sync_on_qbd', 'trade_id');
        $validator = Validator::make($input, FinancialProduct::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $companyId = $this->scope->has() ? $this->scope->id() : 0;
		$category = FinancialCategory::where('company_id', $companyId)
			->findOrFail($input['category_id']);

        $validator = Validator::make($input, FinancialProduct::getRulesByCategory($category));

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (ine($input, 'supplier_id')) {
            if (!$this->supplierRepo->isSupplierExist($input['supplier_id'])) {
                return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Supplier']));
            }

            if ($this->supplierRepo->isSystemSupplier($input['supplier_id'])) {
                return ApiResponse::errorGeneral("You can't add supplier product from here.");
            }
        }

        $message = trans('response.success.saved', ['attribute' => 'Product']);

        if (ine($input, 'sub_id')) {
            $subContractor = User::whereCompanyId($this->scope->id())
                ->whereGroupId(User::GROUP_SUB_CONTRACTOR)
                ->findOrFail($input['sub_id']);

            $category = FinancialCategory::whereName(FinancialCategory::LABOR)
                ->whereCompanyId($this->scope->id())
                ->firstOrFail();

            $input['category_id'] = $category->id;
            $message = trans('response.success.saved', ['attribute' => 'Rate sheet']);
        }

        $name = isSetNotEmpty($input, 'name') ?: null;

        try {
            $product = $this->repo->saveProduct(
                $input['category_id'],
                $name,
                $input['unit'],
                $input['unit_cost'],
                $input
            );

            return ApiResponse::success([
                'message' => $message,
                'data' => $this->response->item($product, new FinancialProductsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Import ABC material list..
     * POST /financial_products/import
     *
     * @return Response
     */
    public function importABCProducts()
    {
        try {
            $input = Request::onlyLegacy('file', 'supplier_id');
            $validator = Validator::make($input, FinancialProduct::getImportFileRules());

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            $import = App::make(ImportProducts::class);
            $import->import($input['file'], $input['supplier_id']);

            return ApiResponse::success([
                'message' => trans('response.success.imported', ['attribute' => 'File']),
            ]);
        } catch (InvalidImportTemplate $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }
    }

    /**
     * Display the specified resource.
     * GET /financialproducts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $product = $this->repo->getById($id);

        if(Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => Auth::id()]);
        }
        return ApiResponse::success([
            'data' => $this->response->item($product, new FinancialProductsTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /financialproducts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $product = $this->repo->getById($id);

        if(Auth::user()->isSubContractorPrime() && $product->created_by != Auth::id()) {
            return ApiResponse::errorGeneral(trans('response.error.cannot_update', ['attribute' => 'Product']));
        }


        $rules = [];
        if ($product->supplier && $product->supplier->isSystemSypplier()) {
            $input = Request::onlyLegacy('selling_price');
        } else {
            $input = Request::all();
            $rules = FinancialProduct::getRules($id);
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $companyId = $this->scope->has() ? $this->scope->id() : 0;

		if(ine($input, 'category_id')) {
			$category = FinancialCategory::where('company_id', $companyId)
			->findOrFail($input['category_id']);

			$validator = Validator::make($input, FinancialProduct::getRulesByCategory($category, $id));
			if($validator->fails()) {
				return ApiResponse::validation($validator);
			}
		}

        $message = trans('response.success.updated', ['attribute' => 'Product']);

        // for sub contractor rate sheet set category as labor
        if ($product->labor) {
            $category = FinancialCategory::whereName(FinancialCategory::LABOR)
                ->whereCompanyId($this->scope->id())
                ->firstOrFail();

            $input['category_id'] = $category->id;
            $message = trans('response.success.updated', ['attribute' => 'Rate sheet']);
        }

        if (ine($input, 'abc_additional_data')) {
            $mapAdditionData = [
                "branch" => null,
                "materia" => null,
                "material_description" => null,
                "purch" => null,
                "estimatin" => null,
                "conversion_f" => null
            ];
            $input['abc_additional_data'] = array_merge($mapAdditionData, $input['abc_additional_data']);
        }

        if ($product->update($input)) {
            return ApiResponse::success([
                'message' => $message,
                'data' => $this->response->item($product, new FinancialProductsTransformer)
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'), $e);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /financialproducts/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $product = $this->repo->getById($id);

        if(Auth::user()->isSubContractorPrime() && $product->created_by != Auth::id()) {
            return ApiResponse::errorGeneral(trans('response.error.cannot_delete', ['attribute' => 'Product']));
        }

        $message = trans('response.success.deleted', ['attribute' => 'Product']);
        // delete labor if linked..
        if ($product->labor) {
            $message = trans('response.success.deleted', ['attribute' => 'Rate sheet']);
            // $labor->delete();
        }

        if ($product->delete()) {
            return ApiResponse::success([
                'message' => $message
            ]);
        }

        return ApiResponse::errorInternal(trans('response.error.internal'));
    }

    /**
     * Import the material CSV file.
     * POST /financial_products/material_lists_import
     *
     * @return Response
     */
    public function importMaterialList()
    {
        set_time_limit(0);
        $input = Request::onlyLegacy('file', 'supplier_id');
        $validator = Validator::make($input, FinancialProduct::getMaterialImportFileRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $category = FinancialCategory::whereCompanyId($this->scope->id())
            ->whereName(FinancialCategory::MATERIALS)
            ->first();

        $supplierId = null;

        if (ine($input, 'supplier_id')) {
            $supplier = $this->supplierRepo->getById($input['supplier_id']);
            $supplierId = $supplier->id;
        }

        DB::beginTransaction();
        try {
            $this->service->import($category, $input['file'], $supplierId);
            DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute'=>'Material list']),
			]);
        } catch(\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
        }

    }

    public function saveOrUpdateSubRateSheet()
    {
        $input = Request::all();

        $validator = Validator::make($input, FinancialProduct::getRateSheetRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $subContractor = User::whereCompanyId($this->scope->id())
            ->onlySubContractors()
            ->findOrFail($input['sub_id']);

        $category = FinancialCategory::whereCompanyId($this->scope->id())
            ->whereName(FinancialCategory::LABOR)
            ->firstOrFail();

        $response = $this->repo->saveOrUpdateSubRateSheet($input['sub_id'], $category->id, $input);

        $data = $this->response->collection($response, new SubContractorRateSheetTransformer);
        $data['message'] = trans('response.success.saved', ['attribute' => 'Rate sheet']);

        return ApiResponse::success($data);
    }

    /**
	 * import labor
	 *
	 * POST - /financial_products/import_labor
	 *
	 * @return response
	 */
	public function importLabor()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getMaterialImportFileRules());

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$companyId = $this->scope->has() ? $this->scope->id() : 0;
		$category = FinancialCategory::whereCompanyId($companyId)
			->whereName(FinancialCategory::LABOR)
			->firstOrFail();

		DB::beginTransaction();
		try{
			$response = $this->service->import($category, $input['file']);
			DB::commit();

			return ApiResponse::success([
				'message' => "{$response['products_imported']} out of {$response['total_products']} labor(s) imported successfully.",
			]);
		}catch(\Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	public function importProductFile()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getImportProductFileRules());

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$companyId = $this->scope->has() ? $this->scope->id() : 0;
		$category = FinancialCategory::whereCompanyId($companyId)
			->whereName($input['category'])
			->firstOrFail();

		DB::beginTransaction();
		try{
			$response = $this->service->import($category, $input['file']);
			DB::commit();

			return ApiResponse::success([
				'message' => "{$response['products_imported']} out of {$response['total_products']} products imported successfully.",
			]);
		}catch(\Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	/**
	 * copy labor for a company that is created by super admin
	 * POST - /financial_products/copy_system_labor
	 * @return response
	 */
	public function copySystemLabor()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getCopySystemLaborRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$category = FinancialCategory::where('name', FinancialCategory::LABOR)
			->where('company_id', $this->scope->id())
			->firstOrFail();

		$systemCategory = FinancialCategory::where('name', FinancialCategory::LABOR)
			->where('company_id', 0)
			->firstOrFail();

		DB::beginTransaction();

		try {
			$this->repo->copySystemProducts($category, $systemCategory, $input);
			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute'=>'Labor']),
			]);
		} catch (\Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}

	/**
	 * copy products for a company that is created by super admin
	 *
	 * POST - /financial_products/copy_system_products
	 *
	 * @return response
	 */
	public function copySystemProducts()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getCopySystemProductRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$category = FinancialCategory::where('name', $input['category'])
			->where('company_id', $this->scope->id())
			->firstOrFail();

		$systemCategory = FinancialCategory::where('name', $input['category'])
			->where('company_id', 0)
			->firstOrFail();

		DB::beginTransaction();

		try {
			$this->repo->copySystemProducts($category, $systemCategory, $input);
			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute'=>'Product(s)']),
			]);
		} catch(\Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
    }

	/**
	 * export labors
	 *
	 * GET - /financial_products/labor_export
	 *
	 * @return response
	 */
	public function exportCSV()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getExportRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		return $this->service->exportCSV($input['type'], $input);
	}

	public function deleteMultipleMaterials()
	{
		$input = Request::all();

		$validator = Validator::make($input, FinancialProduct::getMultipleDeleteRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		$category = FinancialCategory::where('company_id', getScopeId())->findOrFail($input['category_id']);
        $supplierId = null;

		if(ine($input, 'supplier_id')){
			$supplier = Supplier::findOrFail($input['supplier_id']);
		}

		try {
			$products = $this->service->deleteMaterials($category, $input);

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Product(s)']),
			]);
		} catch (Exception $e) {
    		return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
