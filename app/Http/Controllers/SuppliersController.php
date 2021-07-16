<?php

namespace App\Http\Controllers;

use Request;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\ApiResponse;
use App\Models\SupplierBranch;
use App\Models\CompanySupplier;
use App\Models\FinancialProduct;
use App\Models\SrsShipToAddress;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\SupplierRepository;
use Illuminate\Support\Facades\Validator;
use App\Transformers\SuppliersTransformer;
use App\Transformers\SupplierBranchesTransformer;
use App\Repositories\SupplierBranchesRepository;
use App\Services\VendorService;
use App\Events\VendorCreated;
use App\Events\VendorUpdated;
use App\Events\VendorDeleted;
use App\Exceptions\DuplicateVendor;
use Illuminate\Support\Facades\Event;
use Route;
use Illuminate\Support\Facades\Auth;
use App\Models\Division;

class SuppliersController extends ApiController
{

    protected $scope;
    protected $repo;
    protected $response;
	protected $branchRepo;

    public function __construct(
        Context $scope,
        SupplierRepository $repo,
        Larasponse $response,
        SupplierBranchesRepository $branchRepo,
		VendorService $vendorService
    ) {

        $this->scope = $scope;
        $this->repo = $repo;
        $this->response = $response;
        $this->branchRepo 	= $branchRepo;
		$this->vendorService= $vendorService;

        if(Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * display list of suppliers
     * GET - /suppliers
     *
     * @return response
     */
    public function index()
    {
        $input = Request::all();
        $suppliers = $this->repo->getFilteredSuppliers($input);

        $limit = ine($input, 'limit') ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($suppliers->get(), new SuppliersTransformer));
        }

        $suppliers = $suppliers->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($suppliers, new SuppliersTransformer));
    }

    /**
     * add suppliers
     * POST - /suppliers
     *
     * @return response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, [
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $supplier = $this->repo->getFilteredSuppliers($input)->exists();

        if ($supplier) {
            return ApiResponse::errorGeneral(trans('response.error.already_exist', ['attribute' => 'Supplier name']));
        }

        DB::beginTransaction();
		try {
			$supplier = $this->repo->create($input);
			$vendor = $this->vendorService->saveSupplierVendor($supplier, $input);

			DB::commit();
			Event::fire('JobProgress.Events.VendorCreated', new VendorCreated($vendor));
            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Supplier']),
                'data' => $this->response->item($supplier, new SuppliersTransformer)
            ]);
		}catch(DuplicateVendor $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		}
    }

    /**
     * update suppliers
     * PUT - /suppliers/{id}
     *
     * @param  $id
     * @return response
     */
    public function update($id)
    {
        $input = Request::all();
        $validator = Validator::make($input, ['name' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $supplier = $this->repo->getFilteredSuppliers()
            ->whereId($id)
            ->firstOrFail();

        $existingSupplier = $this->repo->getFilteredSuppliers($input)->first();

        if ($existingSupplier && ($existingSupplier->id !== $supplier->id)) {
            return ApiResponse::errorGeneral(trans('response.error.already_exist', ['attribute' => 'Supplier name']));
        }

        DB::beginTransaction();
		try {
			$supplier = $this->repo->update($supplier, $input);
			$vendor = $this->vendorService->updateSupplierVendor($supplier, $input);

			DB::commit();
			if($vendor) {
				Event::fire('JobProgress.Events.VendorUpdated', new VendorUpdated($vendor));
			}

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Supplier']),
                'data' => $this->response->item($supplier, new SuppliersTransformer),
            ]);
		}catch(DuplicateVendor $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		}
    }

    /**
     * delete suppliers
     * DELETE - /suppliers/{id}
     *
     * @param  $id
     * @return response
     */
    public function destroy($id)
    {
        $supplier = $this->repo->getById($id);

        // delete financial products
        $supplier->financialProducts()->update([
			'deleted_at' => Carbon::now()->toDateTimeString(),
			'deleted_by' => \Auth::id(),
			'delete_trigger_action' => Route::currentRouteAction(),
		]);

        // delete supplier details
        $supplier->companySupplier()->delete();

        $supplier->delete();

        //delete vendor supplier
		$vendor = $this->vendorService->deleteOrRestoreSupplierVendor($supplier);

		if($vendor) {
			Event::fire('JobProgress.Events.VendorDeleted', new VendorDeleted($vendor));
		}

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Supplier']),
        ]);
    }

    /**
     * list activated suppliers of a company
     * GET - /suppliers/company_suppliers
     *
     * @return response
     */
    public function listCompanySuppliers()
    {
        $input = Request::all();
		$with = $this->getCompanySupplierIncludes($input);

        $company = Company::findOrFail($this->scope->id());
        $suppliers = $company->suppliers()
            ->whereNull('company_supplier.deleted_at')
            ->with($with)
            ->orderBy('company_supplier.id', 'asc')
            ->get();

        return ApiResponse::success(
            $this->response->collection($suppliers, new SuppliersTransformer)
        );
    }

    /**
     * activate suppliers
     * POST - /suppliers/activate_suppliers
     *
     * @return response
     */
    public function activateSuppliers()
    {
        $input = Request::onlyLegacy('supplier_id');

        $validator = Validator::make($input, [
            'supplier_id' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::findOrFail($this->scope->id());
        $supplier = $this->repo->getFilteredSuppliers($input)->firstOrFail();

        if ($supplier->name != Supplier::ABC_SUPPLIER) {
            return ApiResponse::errorGeneral('Invalid Supplier.');
        }

        /**
        $srsSupplier = Supplier::whereName(Supplier::SRS_SUPPLIER)
            ->whereNull('company_id')
            ->firstOrFail();

        $srsSupplierActive = CompanySupplier::whereCompanyId($this->scope->id())
            ->whereSupplierId($srsSupplier->id)
            ->withTrashed()
            ->latest()
            ->first();

        if ($srsSupplierActive && is_null($srsSupplierActive->deleted_at)) {
            return ApiResponse::errorGeneral(trans('response.error.deactivate_supplier', ['supplier' => Supplier::SRS_SUPPLIER]));
        }
        */

        // if ($srsSupplierActive && $srsSupplierActive->deleted_at) {
        //     $date = Carbon::parse($srsSupplierActive->deleted_at);
        //     $now = Carbon::now();
        //     $diffInMints = $date->diffInMinutes($now);

        //     if ($diffInMints < config('jp.supplier_time_limit')) {
        //         $date = $now->addMinutes(config('jp.supplier_time_limit') - $diffInMints);

        //         return ApiResponse::errorGeneral('Please retry after ' . $date->diffForHumans());
        //     }
        // }

        try {
            if (!$supplier->company_id && !$supplier->companySupplier) {
                $companySupplier = new CompanySupplier;
                $companySupplier->company_id = $this->scope->id();
                $companySupplier->supplier_id = $supplier->id;
                $companySupplier->save();

                //restore vendor supplier
				$vendor = $this->vendorService->deleteOrRestoreSupplierVendor($supplier, $input['supplier_id']);
            }

            return ApiResponse::success([
                'message' => trans('response.success.activated', ['attribute' => 'Supplier']),
                'data' => $this->response->item($supplier, new SuppliersTransformer),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * deactivate suppliers
     * DELETE - /suppliers/deactivate_suppliers
     *
     * @return response
     */
    public function deactivateSuppliers()
    {
        $input = Request::onlyLegacy('supplier_id');

        $validator = Validator::make($input, [
            'supplier_id' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::findOrFail($this->scope->id());
        $supplier = $this->repo->getFilteredSuppliers($input)->firstOrFail();

        try {
            if (!$supplier->company_id && ($companySupplier = $supplier->companySupplier)) {
                
                $shipToAddresses = SrsShipToAddress::where('company_id', $this->scope->id())
                    ->where('company_supplier_id', $companySupplier->id);
                
                $branches = SupplierBranch::where('company_id', $this->scope->id())
                    ->where('company_supplier_id', $companySupplier->id);
                
                DB::table('ship_to_address_branches')
                ->whereIn('srs_ship_to_address_id', $shipToAddresses->pluck('id')->toArray())
                ->whereIn('supplier_branch_id', $branches->pluck('id')->toArray())
                ->delete();
                
                $shipToAddresses->delete();
                $branches->delete();
                
                $supplier->companySupplier()->delete();

                // delete financial products
                FinancialProduct::whereCompanyId($this->scope->id())
                ->whereSupplierId($input['supplier_id'])
                ->update([
                    'deleted_at' => Carbon::now()->toDateTimeString(),
                    'deleted_by' => Auth::id(),
                    'delete_trigger_action' => Route::currentRouteAction(),
                ]);

                //deactivate vendor supplier
                $this->vendorService->deleteOrRestoreSupplierVendor($supplier);
            }

            return ApiResponse::success([
                'message' => trans('response.success.deactivated', ['attribute' => 'Supplier']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get branch list of a supplier
     *
     * GET - /suppliers/branch_list
     * 
     * @return response
     */
    public function branchList()
    {
        $input = Request::all();
        $validator = Validator::make($input, [
            'supplier_id' => 'required'
        ]);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $supplier = $this->repo->getFilteredSuppliers($input)->firstOrFail();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if(!($companySupplier = $supplier->companySupplier)) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'Supplier']));
        }
        $branchList = $this->branchRepo->getFilteredBranches($companySupplier, $input);
        if(!$limit) {
            $branchList = $branchList->get();
            return ApiResponse::success($this->response->collection($branchList, new SupplierBranchesTransformer));
        }
        $branchList = $branchList->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($branchList, new SupplierBranchesTransformer));
    }

    /**
	 * Assign division to branch
	 * POST - /suppliers/branches/assign_division
	 *
	 * @return Json
	 */
	public function assignDivisions()
	{
		$input = Request::all();
		$validator = Validator::make($input, SupplierBranch::getAssignDivisionRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		$details = array_filter($input['details']);

		if(!empty($details)) {
			$defaultBranch = array_filter(array_column($details, 'default_company_branch'));
			if(count($defaultBranch) > 1) {
				return ApiResponse::errorGeneral(trans('response.error.single_default_branch_only'));
			}

			// check duplicate division ids
			$divisionIds = array_column($details, 'division_id');
			if(count(array_unique($divisionIds)) != count($divisionIds)) {

				return ApiResponse::errorGeneral(trans('response.error.division_assign_to_multiple_branches'));
			}

			// check invalid branch ids
			$branchIds = arry_fu(array_column($details, 'branch_id'));
			$branches = SupplierBranch::where('company_id', getScopeId())
				->whereIn('id', $branchIds)
				->get();
			if(count($branchIds) != $branches->count()) {

				return ApiResponse::errorGeneral(trans('response.error.invalid_branch_ids'));
			}

			// check invalid division ids
			$divisions = Division::where('company_id', getScopeId())
				->whereIn('id', $divisionIds)
				->get();
			if(count($divisionIds) != $divisions->count()) {

				return ApiResponse::errorGeneral(trans('response.error.invalid_division_ids'));
			}
		}

		$this->branchRepo->assignDivisions($details);

		return ApiResponse::success([
			'message' => trans('response.success.updated', ['attribute' => 'Branch(s)'])
		]);
	}

	/********** Private Section **********/

	private function getCompanySupplierIncludes($input)
	{
		$with = ['companySupplier'];

		$includes = (array)issetRetrun($input, 'includes') ?: [];

		if(in_array('queue_status', $includes)) {
			$with[] = 'companySupplier.queueStatus';
		}


		return $with;
    }
}
