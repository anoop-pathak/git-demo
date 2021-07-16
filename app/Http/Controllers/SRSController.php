<?php

namespace App\Http\Controllers;

use App\Http\Requests\SRSConnectRequest;
use App\Http\Requests\SRSSubmitOrderRequest;
use App\Models\ApiResponse;
use App\Models\MaterialList;
use App\Models\SRSOrder;
use App\Models\Supplier;
use App\Repositories\MaterialListRepository;
use App\Repositories\SupplierRepository;
use App\Services\Contexts\Context;
use App\Services\SRS\SRSService;
use App\Transformers\MaterialListTransformer;
use App\Transformers\SRSOrdersTransformer;
use App\Transformers\SuppliersTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\SRSShipToAddressesTransformer;
use App\Exceptions\SRSInactiveBranchException;
use App\Exceptions\SRSInvalidShipToAddressException;
use App\Exceptions\SRSInvalidInvoiceDetailException;
use App\Exceptions\SRSSmartTemplatesNotFoundException;
use App\Exceptions\Queue\JobAlreadyInQueueException;
use App\Exceptions\SRSOrderAlreadyPlaced;
use App\Models\SupplierBranch;

class SRSController extends ApiController
{

    protected $service;
    protected $supplierRepo;
    protected $materialListRepo;
    protected $response;

    /**
     * Class Constructor
     */
    public function __construct(SRSService $service, SupplierRepository $supplierRepo, MaterialListRepository $materialListRepo, Larasponse $response, Context $scope)
    {
        $this->service = $service;
        $this->supplierRepo = $supplierRepo;
        $this->materialListRepo = $materialListRepo;
        $this->response = $response;
        $this->scope = $scope;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * connect to srs
     *
     * POST - /srs/connect
     *
     * @return response
     */
    public function connect(SRSConnectRequest $request)
    {
        $input = $request->all();

        /**
        $abcSupplier = Supplier::whereName(Supplier::ABC_SUPPLIER)->firstOrFail();
        $abcSupplierActive = CompanySupplier::whereCompanyId($this->scope->id())
            ->whereSupplierId($abcSupplier->id)
            ->withTrashed()
            ->latest()
            ->first();

        if ($abcSupplierActive && is_null($abcSupplierActive->deleted_at)) {
            return ApiResponse::errorGeneral(trans('response.error.deactivate_supplier', ['supplier' => 'ABC supplier']));
        }

        if ($abcSupplierActive && $abcSupplierActive->deleted_at) {
            $date = Carbon::parse($abcSupplierActive->deleted_at);
            $now = Carbon::now();
            $diffInMints = $date->diffInMinutes($now);

            if ($diffInMints < config('jp.supplier_time_limit')) {
                $date = $now->addMinutes(config('jp.supplier_time_limit') - $diffInMints);

                return ApiResponse::errorGeneral('Please retry after ' . $date->diffForHumans());
            }
        }
        **/

        $srsSupplier = Supplier::srs();

        try {
            if ($srsSupplier->companySupplier) {
                return ApiResponse::errorGeneral(trans('response.error.already_connected', ['attribute' => 'SRS']));
            }

            $response = $this->service->connect($input);

        } catch (SRSInvalidInvoiceDetailException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.connected', ['attribute' => 'SRS']),
            'data' => $this->response->item($response, new SuppliersTransformer)
        ]);
    }

    /**
     * get price list of products
     *
     * GET - /srs/get_price_list
     *
     * @return response
     */
    public function getPriceList()
    {
        $input = Request::onlyLegacy('material_list_id', 'item_detail', 'branch_code', 'ship_to_sequence_number', 'stop_price_compare');

        $validator = Validator::make($input, [
            'branch_code'               => 'required',
            'material_list_id'          => 'required_without:item_detail',
            'item_detail'               => 'required_without:material_list_id|array|nullable',
            'ship_to_sequence_number'   => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $supplier = Supplier::srs();

        if (!$supplier->companySupplier) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
        }

        try {
            $response = $this->service->getPriceList($supplier, $input);

            return ApiResponse::success([
                'data'          => $response['active_items'],
                'deleted_items' => $response['deleted_items']
            ]);
        } catch (SRSInactiveBranchException $e) {
            return ApiResponse::errorGeneral($e->getMessage(), [], ['deleted' => 'branch']);
        } catch (SRSInvalidShipToAddressException $e) {
            return ApiResponse::errorGeneral($e->getMessage(), [], ['deleted' => 'ship_to_address']);
        } catch (\Exception $e) {
            
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * submit order
     *
     * POST - /srs/submit_order
     *
     * @return response
     */
    public function submitOrder(SRSSubmitOrderRequest $request)
    {
        $data = $request->all();

        $materialList = $this->materialListRepo->getById($data['material_list_id']);

        if ($materialList->type != MaterialList::MATERIAL_LIST) {
            return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'Material list']));
        }

        $supplier = Supplier::srs();

        if (!$supplier->companySupplier) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
        }

        if ($materialList->srsOrder) {
            return ApiResponse::errorGeneral(trans('response.error.order_already_placed'));
        }

        try {
			$responseData = $this->service->submitOrder($supplier, $materialList, $data);

			return ApiResponse::success([
				'message' => trans('response.success.submitted', ['attribute' => 'Order']),
				'data' 	  => $this->response->item($responseData, new MaterialListTransformer)
			]);
		} catch (SRSOrderAlreadyPlaced $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * get order by order_id
     *
     * GET - /srs/order_detail/{id}
     *
     * @param  $orderId
     * @return response
     */
    public function orderDetail($orderId)
    {
        $order = SRSOrder::whereOrderId($orderId)->firstOrFail();

        return ApiResponse::success([
            'data' => $this->response->item($order, new SRSOrdersTransformer)
        ]);
    }

    /**
     * list ship to address
     *
     * GET - /srs/ship_to_address_list
     * 
     * @return response
     */
    public function shipToAddressList()
    {
        $input = Request::all();
        $srsSupplier = Supplier::srs();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        if(!($companySupplier = $srsSupplier->companySupplier)) {
            return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
        }
        $transFormer = new SRSShipToAddressesTransformer;
        $shipToAddresses = $this->service->getShipToaddresses($companySupplier, $input);
        $query = clone $shipToAddresses;
        // set default include if only 1 ship to address with 1 branch exist
        if((int)$query->count() === 1) {
            $transFormer->setDefaultIncludes(['branches']);
        }
        if(!$limit) {
            $shipToAddresses = $shipToAddresses->get();
            return ApiResponse::success($this->response->collection($shipToAddresses, $transFormer));
        }
        $shipToAddresses = $shipToAddresses->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($shipToAddresses, $transFormer));
    }

    /**
	 * Get SRS Smart Templates Listing
	 * GET - /srs/smart_templates
	 * @return response
	 */
	public function getSmartTemplates()
	{
		$input = Request::all();
		$srsSupplier = Supplier::srs();
		if(!($companySupplier = $srsSupplier->companySupplier)) {
			return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
		}

		$validator = Validator::make($input, [
			'branch_code' => 'required',
		]);
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$branch = SupplierBranch::where('company_id', $this->scope->id())
			->where('company_supplier_id', $companySupplier->id)
			->where('branch_code', $input['branch_code'])
			->firstOrFail();

		try {
			$data = $this->service->getSmartTemplates($companySupplier, $branch);

			return ApiResponse::success(['data' => $data]);
		} catch (SRSSmartTemplatesNotFoundException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * sync products of a branch	 *
	 * PUT - /srs/branch/{id}/update_products
	 *
	 * @param  Integer | $id | Id of branch
	 * @return [type]           [description]
	 */
	public function updateBranchProducts($id)
	{
		$srsSupplier = Supplier::srs();
		if(!($companySupplier = $srsSupplier->companySupplier)) {

			return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
		}

		$branch = SupplierBranch::where('company_id', $this->scope->id())
			->where('company_supplier_id', $companySupplier->id)
			->findOrFail($id);

		try {
			$response = $this->service->syncBranchProducts($companySupplier, $branch);

			return ApiResponse::success(['message' => trans('response.success.added_in_syncing_queue', ['attribute' => 'Branch'])]);
		} catch (JobAlreadyInQueueException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (SRSInactiveBranchException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * update all srs details by queue
	 * PUT - /srs/update_details
	 *
	 * @return response
	 */
	public function updateDetails()
	{
		$input = Request::all();
		$srsSupplier = Supplier::srs();
		if(!($companySupplier = $srsSupplier->companySupplier)) {
			return ApiResponse::errorGeneral(trans('response.error.not_connected', ['attribute' => 'SRS']));
		}

		try {
			$this->service->updateAllDetails($companySupplier);

			return ApiResponse::success(['message' => trans('response.success.added_in_syncing_queue', ['attribute' => 'SRS catalog'])]);
		} catch (JobAlreadyInQueueException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (SRSInactiveBranchException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
