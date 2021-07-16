<?php
namespace App\Http\Controllers;

use App\Transformers\VendorBillsTransformer;
use App\Repositories\VendorBillRepository;
use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;
use App\Services\VendorBillService;
use App\Exceptions\InvalideAttachment;
use App\Repositories\VendorRepository;
use App\Events\VendorBillDeleted;
use App\Exceptions\NotUpdateVendorBillWithTaxAmount;
use App\Events\VendorBillCreated;
use App\Events\VendorBillUpdated;
use App\Exceptions\MinVendorBillAmountException;
use Request;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\VendorBill;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class VendorBillsController extends ApiController
{

	/**
	 * Vendor Repo
	 * @var \JobProgress\Repositories\VendorBillRepository
	 */
	protected $repo;

	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	protected $response;

	/**
	 * Set Company Scope
	 * @return company scope
	 */
	protected $scope;

	public function __construct(Larasponse $response, VendorBillRepository $repo, VendorBillService $service, Context $scope, VendorRepository $vendorRepo)
	{
		$this->repo     = $repo;
		$this->scope    = $scope;
		$this->service  = $service;
		$this->response = $response;
		$this->vendorRepo = $vendorRepo;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	public function index()
	{
		$input = Request::all();

		$vendorBills = $this->repo->getFilteredVendors($input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$vendorBills = $vendorBills->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($vendorBills,new VendorBillsTransformer));
	}

	/**s
	 * Store a newly created resource in storage.
	 * POST /vendor_bills
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, VendorBill::getCreateRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		$vendor = $this->vendorRepo->getById($input['vendor_id']);
		try {
			$vendorBill = $this->service->createVendorBills($input['job_id'], $input['vendor_id'], $input['bill_date']
				, $input['lines'], $input);

			Event::fire('JobProgress.Events.VendorBillCreated', new VendorBillCreated($vendorBill));

			return ApiResponse::success([
				'message' => trans('response.success.created', ['attribute' => 'Vendor Bill']),
				'data' => $this->response->item($vendorBill, new VendorBillsTransformer)
			]);
		} catch(MinVendorBillAmountException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalideAttachment $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {
			$message = class_basename($e->getModel()).' Not Found';

			return ApiResponse::errorNotFound($message);
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
	/**
	 * Update the specified resource in storage.
	 * PUT /VendorBills/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::all();

		$validator = Validator::make($input, VendorBill::getUpdateRules($id));
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		$vendorBill = $this->repo->getById($id);
		$vendor = $this->vendorRepo->getById($input['vendor_id']);
		try {
			$VendorBill = $this->service->updateVendorBills($vendorBill, $input['vendor_id'], $input['bill_date'], $input['lines'], $input);

			Event::fire('JobProgress.Events.VendorBillUpdated', new VendorBillUpdated($vendorBill));

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Vendor Bill']),
				'data' => $this->response->item($VendorBill, new VendorBillsTransformer)
			]);
		} catch(MinVendorBillAmountException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(InvalideAttachment $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(NotUpdateVendorBillWithTaxAmount $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {
			$message = class_basename($e->getModel()).' Not Found';

			return ApiResponse::errorNotFound($message);
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * DELETE /VendorBills/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$vendorBill = $this->repo->getById($id);
		try {
			$vendorBill->lines()->delete();
			$vendorBill->delete();
			Event::fire('JobProgress.Events.VendorBillDeleted', new VendorBillDeleted($vendorBill));

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Vendor Bill']),
			]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function show($id)
	{
		try {
			$vendorBill = $this->repo->getById($id);

			return ApiResponse::success(['data' => $this->response->item($vendorBill,new VendorBillsTransformer)]);
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}