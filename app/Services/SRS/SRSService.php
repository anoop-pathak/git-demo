<?php

namespace App\Services\SRS;

use App\Models\Company;
use App\Models\CompanySupplier;
use App\Models\FinancialDetail;
use App\Models\FinancialProduct;
use App\Models\MaterialList;
use App\Models\SRSOrder;
use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Queue;
use App\Models\SupplierBranch;
use Exception;
use App\Exceptions\SRSInactiveBranchException;
use App\Exceptions\SRSInvalidShipToAddressException;
use App\Exceptions\SRSInvalidInvoiceDetailException;
use App\Models\SrsShipToAddress;
use App\Repositories\FinancialProductsRepository;
use App\Exceptions\SRSSmartTemplatesNotFoundException;
use JobQueue;
use App\Transformers\FinancialProductsTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\Queue\JobAlreadyInQueueException;
use Illuminate\Database\QueryException;
use App\Exceptions\SRSOrderAlreadyPlaced;

class SRSService
{
    protected $guzzleClient;
    protected $supplierRepo;
    protected $scope;
    protected $inActiveItems = [];

    /**
     * Class Constructor
     */
    public function __construct(SupplierRepository $supplierRepo, Context $scope)
    {
        $this->request = new Client([
            'headers' => [
                'client_id' => config('srs.client_id'),
                'client_secret' => config('srs.client_sec')
            ]
        ]);

        $this->supplierRepo = $supplierRepo;
        $this->scope = $scope;
    }

    /**
     * connect srs
     * @param  [array] $input [array of input fields]
     * @return boolean
     */
    public function connect($input)
    {
        $input['account_number'] = explode(' ', $input['account_number'])[0];
        $data = [
            'accountNumber' => $input['account_number'],
            'invoiceNumber' => $input['invoice_number'],
        ];

        if (ine($input, 'billed_amount')) {
            $data['billedAmount']  = $input['billed_amount'];
        }
        if (ine($input, 'invoice_date')) {
            $data['invoiceDate']  = $input['invoice_date'];
        }

        $response = $this->request->get(config('srs.customer_base_url').'customer/validate', ['query' => $data]);

        $response = json_decode($response->getBody(), true);

        if ($response['validIndicator'] == 'N') {
            throw new SRSInvalidInvoiceDetailException("Invalid invoice detail.");
        }

        $supplier = $this->connectByAccountNumber($input);

        return $supplier;
    }

    /**
     * get price list of products
     *
     * @param object $supplier
     * @param object $materialList
     *
     * @return $ret
     */
    public function getPriceList($supplier, $input)
    {
        $materialList = MaterialList::find($input['material_list_id']);
        try {
            $productCodeList = [];

            $ret = [
                'active_items'  => [],
                'deleted_items' => [],
            ];

            $srsSupplier = $supplier->companySupplier;

            // check branch & ship to address is valid or not
			if(!ine($input, 'stop_price_compare')) {
				$this->validateBranchAndShipToAddress($srsSupplier, $input);
			}

            $data = [
                'sourceSystem' => config('srs.source_system'),
                'customerId' => $srsSupplier->srs_account_number,
                'branchCode'    => $input['branch_code'],
                'shipToSequenceNumber' => (int)$input['ship_to_sequence_number']
            ];

            $sellingPriceList = [];

            if ($materialList) {
                $financialDetails = FinancialDetail::whereCompanyId($this->scope->id())
                    ->whereWorksheetId($materialList->worksheet_id)
                    ->whereSupplierId($supplier->id)
                    ->get();

                $currentPricing  = $financialDetails->pluck('unit_cost', 'product_code')->toArray();
                // $currentUnitList = $financialDetails->pluck('unit', 'product_code')->toArray();

                foreach ($financialDetails as $key => $financialDetail) {
                    if (!$financialDetail->product_code) {
                        continue;
                    }

                    $data['itemCodeList'][$key]['itemCode'] = $financialDetail->product_code;
                    $data['itemCodeList'][$key]['quantity'] = 1;
                    $data['itemCodeList'][$key]['option']	= $financialDetail->color ?: '';
					$data['itemCodeList'][$key]['UOM']		= $financialDetail->unit;
                }
            } else {
                $requestedCodes = [];
                foreach ($input['item_detail'] as $key => $item) {
                    if(!isset($item['item_code'])) continue;
                    $requestedCodes[] = $item['item_code'];
                    $data['itemCodeList'][$key]['itemCode'] = $item['item_code'];
                    $data['itemCodeList'][$key]['option']	= ine($item, 'color') ? $item['color'] : '';
					$data['itemCodeList'][$key]['UOM']		= ine($item, 'unit') ? $item['unit'] : '';

                    $currentPricing[$item['item_code']]  = isset($item['price']) ? $item['price'] : 0;
                    // $currentUnitList[$item['item_code']] = isset($item['unit']) ? $item['unit'] : '';
                }

                if(ine($input, 'stop_price_compare')) {
					$financialRepo = app(FinancialProductsRepository::class);
					$sellingPriceList = $financialRepo->make()
						->where('branch_code', $input['branch_code'])
						->whereIn('code', $requestedCodes)
                        ->pluck('selling_price', 'code')
                        ->toArray();
                }
            }

            if (!ine($data, 'itemCodeList')) {
                return $ret;
            }

            $ret = [];

            // remove duplicate values
            $data['itemCodeList'] = array_values(array_unique($data['itemCodeList'], SORT_REGULAR));

            // get price from srs
            $response = $this->getPrices($data);


            foreach ($response as $key => $value) {

				$price = numberFormat($value['price'], 2);

                if($price == 0) {
					$this->inActiveItems[] = [
						'itemCode'		=> $value['requestedItemCode'],
						'message'		=> $value['message'],
						'message_code'	=> $value['messageCode'],
					];

                    continue;
				}

                if(!ine($input, 'stop_price_compare')) {
					$currentPrice = null;
					if(isset($currentPricing[$value['requestedItemCode']])) {
						$currentPrice = numberFormat($currentPricing[$value['requestedItemCode']]);
					}

					$actualPrice = numberFormat($price / $value['UOMConversionFactor']);

					if (($currentPrice == $actualPrice)) continue;
				}

				$ret[$key]['item_code']		= $value['requestedItemCode'];
				$ret[$key]['price']			= $price;
				$ret[$key]['unit']			= $value['priceUOM'];
				$ret[$key]['color']			= $value['requestedOption'];
				$ret[$key]['message']		= $value['message'];
                $ret[$key]['message_code']	= $value['messageCode'];
                $ret[$key]['unit_conversion_factor']	= $value['UOMConversionFactor'];
				$ret[$key]['selling_price']				= issetRetrun($sellingPriceList, $value['requestedItemCode']) ?: null;
            }

            // delete in-active products
            if(!empty($this->inActiveItems)) {
                FinancialProduct::whereIn('code', array_column($this->inActiveItems, 'itemCode'))
                    ->where('company_id', $this->scope->id())
                    ->where('supplier_id', $supplier->id)
                    ->where('branch_code', $input['branch_code'])
                    ->update(['active' => false]);
            }

            return [
                'active_items'   => array_values($ret),
                'deleted_items' => array_values($this->inActiveItems),
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * submit order
     *
     * @param  object $supplier
     * @param  object $materialList
     * @param  array $input
     *
     * @return boolean
     */
    public function submitOrder($supplier, $materialList, $input)
    {
        $srsSupplier = $supplier->companySupplier;
        $job         = $materialList->job;
        $worksheet   = $materialList->worksheet;
        $poNumber	 = $materialList->serial_number;

        $uniqueId = uniqueTimestamp();
        $transactionDate = Carbon::now()->format('Y-m-d\TH:i:s');

        $this->headers['guid'] = $uniqueId;

        $shippingMethod = 'Ground drop';
        if(isset($input['po_details']['shipping_method'])
            && in_array($input['po_details']['shipping_method'], ['Ground drop', 'Willcall'])) {
            $shippingMethod = $input['po_details']['shipping_method'];
        }

        $poDetails = [
            'reference' => $uniqueId,
            'jobNumber' => $job->number,
            'poNumber' => $poNumber,
            'orderDate' => Carbon::now()->format('Y-m-d'),
            'expectedDeliveryDate' => $input['po_details']['expected_delivery_date'],
            'expectedDeliveryTime' => isset($input['po_details']['expected_delivery_time']) ? $input['po_details']['expected_delivery_time'] : '',
            'timezone' => isset($input['po_details']['timezone']) ? $input['po_details']['timezone'] : null,
            'orderType' => config('srs.order_type'),
            'shippingMethod' => $shippingMethod,
        ];

        $shipTo = [
            'city' => $input['ship_to_address']['city'],
            'state' => $input['ship_to_address']['state'],
            'zipCode' => $input['ship_to_address']['zip_code'],
            'addressLine1' => $input['ship_to_address']['address_line1'],
        ];

        if (isset($input['ship_to_address']['address_line2']) && !empty($input['ship_to_address']['address_line2'])) {
            $shipTo['addressLine2'] = $input['ship_to_address']['address_line2'];
        }
        if (isset($input['ship_to_address']['address_line3']) && !empty($input['ship_to_address']['address_line3'])) {
            $shipTo['addressLine3'] = $input['ship_to_address']['address_line3'];
        }

        $billTo = [
            'city' => $input['bill_to']['city'],
            'state' => $input['bill_to']['state'],
            'zipCode' => $input['bill_to']['zip_code'],
        ];

        if (isset($input['bill_to']['address_line1']) && !empty($input['bill_to']['address_line1'])) {
            $billTo['addressLine1'] = $input['bill_to']['address_line1'];
        }
        if (isset($input['bill_to']['address_line2']) && !empty($input['bill_to']['address_line2'])) {
            $billTo['addressLine2'] = $input['bill_to']['address_line2'];
        }
        if (isset($input['bill_to']['address_line3']) && !empty($input['bill_to']['address_line3'])) {
            $billTo['addressLine3'] = $input['bill_to']['address_line3'];
        }

        $customerInfo = [
            'customerContactName' => $input['customer_contact']['name'],
            'customerContactPhone' => $input['customer_contact']['phone'],
            'customerContactEmail' => $input['customer_contact']['email'],
            'customerContactAddress' => [
                'city' => $input['customer_contact']['address']['city'],
                'state' => $input['customer_contact']['address']['state'],
                'zipCode' => $input['customer_contact']['address']['zip_code'],
            ],
        ];

        if (isset($input['customer_contact']['address']['address_line1']) && !empty($input['customer_contact']['address']['address_line1'])) {
            $customerInfo['customerContactAddress']['addressLine1'] = $input['customer_contact']['address']['address_line1'];
        }
        if (isset($input['customer_contact']['address']['address_line2']) && !empty($input['customer_contact']['address']['address_line2'])) {
            $customerInfo['customerContactAddress']['addressLine2'] = $input['customer_contact']['address']['address_line2'];
        }
        if (isset($input['customer_contact']['address']['address_line3']) && !empty($input['customer_contact']['address']['address_line3'])) {
            $customerInfo['customerContactAddress']['addressLine3'] = $input['customer_contact']['address']['address_line3'];
        }

        $financialDetails = FinancialDetail::whereCompanyId($this->scope->id())
            ->whereWorksheetId($materialList->worksheet_id)
            ->whereSupplierId($supplier->id)
            ->get();

        $productList = [];

        foreach ($financialDetails as $key => $financialDetail) {
            $productList[$key]['quantity'] = (float)$financialDetail->quantity;
            $productList[$key]['UOM'] = $financialDetail->unit;
            $productList[$key]['price'] 			= ($financialDetail->unit_cost * 1);
            $productList[$key]['itemCode'] = $financialDetail->product_code;
            $productList[$key]['itemSize'] = $financialDetail->size ? $financialDetail->size : '';
            $productList[$key]['itemDescription']   = $financialDetail->description ? addslashes($financialDetail->description) : '';
            $productList[$key]['customerItem'] = $financialDetail->product_name ? $financialDetail->product_name : '';
            $productList[$key]['option']			= (string)$financialDetail->color;
        }

        $data = [
            'sourceSystem' => config('srs.source_system'),
            'customerCode' => strtoupper($srsSupplier->srs_account_number),
            'branchId' => $worksheet->branch_id,
            'shipToSequenceNumber' => $worksheet->ship_to_sequence_number,
            'accountNumber' => strtoupper($srsSupplier->srs_account_number),
            'transactionID' => $uniqueId,
            'transactionDate' => $transactionDate,
            'shipTo' => $shipTo,
            'billTo' => $billTo,
            'poDetails' => $poDetails,
            'orderLineItemDetails' => $productList,
            'customerContactInfo' => $customerInfo,
        ];

        DB::beginTransaction();

        try {
            $orderDetails = [
                'transaction_date' => $transactionDate,
                'po_details' => $poDetails,
                'ship_to' => $shipTo,
                'bill_to' => $billTo,
                'customer_contact_info' => $customerInfo,
            ];

            if (ine($input, 'notes')) {
                $data['notes'] = $input['notes'];
                $orderDetails['notes'] = $input['notes'];
            }

            // set for testing account
            if ((App::environment() != 'production') || $this->scope->id() == 12) {
                $data['isTest'] = true;
            }

            // save order details
            $order = SRSOrder::create([
                'material_list_id' => $materialList->id,
                'order_id' => $uniqueId,
                'order_details' => $orderDetails,
                'order_status' => SRSOrder::ORDER_PLACED,
            ]);

            $materialList->setRelation('srsOrder', $order);

            $response = $this->request->post(config('srs.submit_order_url'), [
                'json' => $data,
                'headers' => ['guid' => $uniqueId]
            ]);

            $response = json_decode($response->getBody(), true);
            $order->srs_order_id = $response['orderID'];
            $order->save();
        } catch (QueryException $e){
			DB::rollback();
			$errorCode = $e->errorInfo[1];

			if($errorCode == 1062){
				throw new SRSOrderAlreadyPlaced(trans('response.error.order_already_placed'));
			}

			throw $e;

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        DB::commit();

        return $materialList;
    }

    public function connectByAccountNumber($input)
    {
        DB::beginTransaction();
        try {
            $company  = Company::findOrFail($this->scope->id());
            $supplier = Supplier::srs();
            // get customer info
            $customer = $this->request->get(config('srs.customer_base_url').'customers/'.$input['account_number']);
            $customer = json_decode($customer->getBody(), true);

            $companySupplier = new CompanySupplier([
                'srs_account_number'  => strtoupper($input['account_number']),
                'srs_customer_id'     => $customer['customerId'],
            ]);
            $companySupplier->company_id  = $company->id;
            $companySupplier->supplier_id = $supplier->id;
            $companySupplier->save();

            // TODO - need to change queue connection to long_task
            $queueData = [
                'company_supplier_id'   => $companySupplier->id
            ];
            JobQueue::enqueue(JobQueue::CONNECT_SRS, $company->id, $companySupplier->id, $queueData);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        $supplier->setRelation('companySupplier', $companySupplier);
        return $supplier;
    }

    /**
     * get ship to address list
     * @param  Object $companySupplier
     * @param  Array $input
     * @return Object $shipToAddresses
     */
    public function getShipToaddresses($companySupplier, $input)
    {
        $query = $companySupplier->srsShipToAddresses();
        $query->select("srs_ship_to_addresses.*");
        $query->leftJoin('ship_to_address_branches as ship_to_address_branches', 'ship_to_address_branches.srs_ship_to_address_id', '=', 'srs_ship_to_addresses.id')
            ->leftJoin('supplier_branches', 'ship_to_address_branches.supplier_branch_id', '=', 'supplier_branches.id');
        $query->addSelect(DB::raw("COUNT(DISTINCT supplier_branches.id) as total_branches"));
        $query->havingRaw('COUNT(DISTINCT supplier_branches.id) > 0');
        $query->groupBy('srs_ship_to_addresses.id');
        return $query;
    }

    /**
	 * Get SRS Smart Templates
	 * @param  CompanySupplier 	| $companySupplier | Object of CompanySupplier model
	 * @param  SupplierBranch 	| $branch          | Object of SupplierBranch model
	 * @return boolean
	 */
	public function getSmartTemplates($companySupplier, $branch)
	{
		$params = [
			'branchId' => $branch->branch_id,
			'customerCode' => $companySupplier->srs_account_number,
		];

		// set for testing purpose(Temporary)
		if((App::environment() != 'production') && $branch->branch_id == 5) {
			$params = [
				'branchId' => '73',
				'customerCode' => 'HRC253',
			];
		}
		$response = $this->request->get(config('srs.smart_template_url').'templates', ['query' => $params]);
		$response = $response->json();

		$index = arrayCSByValue($response['TEMPLATES'], 'Order History', 'TEMPLATE_MODEL');
		$orderTemplates = issetRetrun($response['TEMPLATES'], $index) ?: [];

		$index = arrayCSByValue($response['TEMPLATES'], 'Branch Order History', 'TEMPLATE_MODEL');
		$branchTemplates = issetRetrun($response['TEMPLATES'], $index) ?: [];

		if(!ine($orderTemplates, 'TEMPLATES') && !ine($branchTemplates, 'TEMPLATES')) {
			throw new SRSSmartTemplatesNotFoundException(trans('response.error.srs_smart_templates_not_found'));
		}

		$data['order_history_templates'] = $this->getTemplateProducts($orderTemplates, $companySupplier, $branch);
		$data['branch_order_histroy_templates'] = $this->getTemplateProducts($branchTemplates, $companySupplier, $branch);
		$data['suggested_products'] = $this->getSuggestedProducts($response, $companySupplier, $branch);

		return $data;
	}

	/**
	 * sync a branch products with srs catalog
	 * @param  CompanySupplier 	| $companySupplier | CompanySupplier details
	 * @param  SupplierBranch 	| $branch          | Branch details
	 * @param  Array 			| $input           | Array of inputs
	 * @return boolean
	 */
	public function syncBranchProducts($companySupplier, $branch)
	{
		if(($queueStatus = $branch->queueStatus)) {
			switch ($queueStatus->status) {
				case JobQueue::STATUS_QUEUED:

					throw new JobAlreadyInQueueException(trans('response.error.srs_queue_status_queued', ['attribute' => 'Branch']));
				case JobQueue::STATUS_IN_PROCESS:

					throw new JobAlreadyInQueueException(trans('response.error.srs_queue_status_in_process', ['attribute' => 'Branch']));
			}

			// $date = Carbon::parse($queueStatus->updated_at);
			// $now  = Carbon::now();
			// $diffInMints = $date->diffInMinutes($now);

			// if($diffInMints < config('jp.srs_branch_product_update_time')) {
			// 	$date = $now->addMinutes(config('jp.srs_branch_product_update_time') - $diffInMints);

			// 	throw new JobAlreadyInQueueException('This branch already updated. Please retry after '.$date->diffForHumans(). '.');
			// }
		}

		$data = [
			'company_supplier_id' => $companySupplier->id,
			'branch_id' => $branch->id,
			'branch_code' => $branch->branch_code,
		];
		JobQueue::enqueue(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $branch->company_id, $branch->id, $data);

		return true;
	}

	/**
	 * update all details of SRS
	 * @param  CompanySupplier | $companySupplier | CompanySupplier info
	 * @return boolean
	 */
	public function updateAllDetails($companySupplier)
	{
		if($queueStatus = $companySupplier->queueStatus) {
			switch ($queueStatus->status) {
				case JobQueue::STATUS_QUEUED:

					throw new JobAlreadyInQueueException(trans('response.error.srs_queue_status_queued', ['attribute' => 'SRS catalog']));
				case JobQueue::STATUS_IN_PROCESS:

					throw new JobAlreadyInQueueException(trans('response.error.srs_queue_status_in_process', ['attribute' => 'SRS catalog']));
			}

			// $date = Carbon::parse($queueStatus->updated_at);
			// $now  = Carbon::now();
			// $diffInMints = $date->diffInMinutes($now);

			// if($diffInMints < config('jp.srs_update_detail_time')) {
			// 	$date = $now->addMinutes(config('jp.srs_branch_product_update_time') - $diffInMints);

			// 	throw new JobAlreadyInQueueException('The SRS details already updated. Please retry after '.$date->diffForHumans());
			// }
		}

		$queueData = [
			'company_supplier_id' => $companySupplier->id
		];

		JobQueue::enqueue(JobQueue::SRS_SYNC_DETAILS, $this->scope->id(), $companySupplier->id, $queueData);

		return true;
	}

    /*************** Private Section ***************/

    /**
     * get branch detail of a customer
     * @param  $branchId SRS branch id
     * @return array $ret
     */
    private function getBranchDetail($branchId)
    {
        if (empty($branchId)) {
            return null;
        }

        $branch = $this->request->get(config('srs.branch_base_url') . 'branch/' . $branchId);

        $branchDetail = json_decode($branch->getBody(), true);

        $ret = [
            'branch_logo' => $branchDetail['branchLogo'],
            'branch_id' => $branchDetail['branchId'],
            'branch_code' => $branchDetail['branchCode'],
            'branch_name' => $branchDetail['branchName'],
            'branch_address' => $branchDetail['branchAddress'],
            'branch_city' => $branchDetail['branchCity'],
            'branch_state' => $branchDetail['branchState'],
            'branch_zip' => $branchDetail['branchZip'],
            'branch_city_state_zip' => $branchDetail['branchCityStateZip'],
            'branch_fax' => $branchDetail['branchFax'],
            'branch_phone' => $branchDetail['branchPhone'],
            'branch_manager' => $branchDetail['branchManager'],
            'branch_manager_email' => $branchDetail['branchManagerEmail'],
            'bussiness_hours' => $branchDetail['businessHours'],
            'sat_bussiness_hours' => $branchDetail['saturdayBusinessHours'],
            'branch_region' => $branchDetail['branchRegion'],
            'branch_region_id' => $branchDetail['branchRegionId'],
            'latitude' => $branchDetail['latitude'],
            'longitude' => $branchDetail['longitude'],
        ];

        return $ret;
    }

    private function getPrices(&$data)
    {
        try {
            $response = $this->request->post(config('srs.product_base_url') . 'products/price', ['json' => $data]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errRes = $e->getResponse();
            $err = json_decode($errRes->getBody()->getContents());

            Log::info("--------- Validate Products - SRS Pricing API Error Start ---------");
			Log::info("Error Detail ".print_r($err, 1));
			Log::info("--------- Request Data ---------");
			Log::info(json_encode($data));
			Log::info("--------- Validate Products - SRS Pricing API Error Ends ---------");

            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * check branch and ship to address exists on SRS or inactive
     * @param  Object $companySupplier CompanySupplier
     * @param  Array $input
     * @return boolean
     */
    private function validateBranchAndShipToAddress($companySupplier, $input)
    {
        $shipToAddress = $this->request->get(config('srs.customer_base_url').'customers/'.$companySupplier->srs_account_number.'/shipToAddresses');
        $shipToAddresses = json_decode($shipToAddress->getBody(), true);
        if (!in_array($input['ship_to_sequence_number'], array_column($shipToAddresses['shipToList'], 'shipToSequenceId')  )) {
            SrsShipToAddress::where('company_id', $this->scope->id())
                ->where('ship_to_sequence_id', $input['ship_to_sequence_number'])
                ->delete();
            throw new SRSInvalidShipToAddressException(trans('response.error.invalid', ['attribute' => 'ship to address']));
        }
        $eligibleBranches = $this->request->get(config('srs.branch_base_url').'branch/customer/'.$companySupplier->srs_account_number.'?shipToSequenceNumber='.$input['ship_to_sequence_number']);
        $eligibleBranches = json_decode($eligibleBranches->getBody(), true);
        if (!in_array($input['branch_code'], array_column($eligibleBranches['branchList'], 'branchCode'))) {
            SupplierBranch::where('company_id', $this->scope->id())
                ->where('branch_code', $input['branch_code'])
                ->delete();
            throw new SRSInactiveBranchException("Inactive branch.");
        }
        return true;
    }

    /**
	 * get template products details by product codes
	 * @param  Array 			| $templates		| Array of SRS Templates
	 * @param  CompanySupplier 	| $companySupplier 	| Object of CompanySupplier
	 * @param  SupplierBranch 	| $branch 			| Object of SupplierBranch
	 * @return $data
	 */
	private function getTemplateProducts($templates, $companySupplier, $branch)
	{
		$accurateTemplates = array_slice($templates['TEMPLATES'], 0, 2);
		$resClient = app(Larasponse::class);

		$data = [];
		foreach ($accurateTemplates as $key => $value) {
			$products = FinancialProduct::where('company_id', getScopeId())
				->where('supplier_id', $companySupplier->supplier_id)
				->where('branch_code', $branch->branch_code)
				->with(['category', 'supplier'])
				->whereIn('code', array_filter(array_values($value['TEMPLATE'])))
				->get();
			$data[$key]['template_number'] = $value['TEMPLATE_NUMBER'];
			$data[$key]['template_name']   = 'template_'.$value['TEMPLATE_NUMBER'];
			$data[$key]['template_products'] = $resClient->collection($products, new FinancialProductsTransformer)['data'];
		}

		return $data;
	}

	/**
	 * get template suggested product details by product codes
	 * @param  Array 			| $response 		| SRS Template API response
	 * @param  CompanySupplier 	| $companySupplier 	| Object of CompanySupplier
	 * @param  SupplierBranch 	| $branch 			| Object of SupplierBranch
	 * @return $data
	 */
	private function getSuggestedProducts($response, $companySupplier, $branch)
	{
		$resClient = app(Larasponse::class);
		$itemCodes = [];

		// convert all item details from nested array to sindle level array
		$suggestedItems = array_column($response['ITEMS'], 'ITEMS');
		$itemDetails = call_user_func_array('array_merge', $suggestedItems);

		$products = FinancialProduct::where('company_id', getScopeId())
			->where('supplier_id', $companySupplier->supplier_id)
			->where('branch_code', $branch->branch_code)
			->whereIn('code', arry_fu(array_column($itemDetails, 'ITEM_GENERIC_DESC')))
			->with(['category', 'supplier'])
			->get();

		$products = $resClient->collection($products, new FinancialProductsTransformer)['data'];

		$data = [];
		foreach ($products as $key => $product) {
			$index = arrayCSByValue($itemDetails, $product['code'], 'ITEM_GENERIC_DESC');
			$itemDetail = $itemDetails[$index];
			$product['rank'] = $itemDetail['RANK'];
			$product['probability'] = $itemDetail['PROBABILITY'];
			$data[] = $product;
		}

		// sort array by rank and probability
		array_multisort(array_column($data, 'probability'), SORT_DESC, array_column($data, 'rank'), SORT_ASC, $data);

		return $data;
	}
}
