<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialDetail;
use App\Models\MaterialList;
use App\Models\SRSOrder;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use GuzzleHttp\Client;
class ResubmitSRSOrder extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:resubmit_srs_order';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resubmit canceled SRS orders.';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
 		$companyId = (int)$this->ask('Enter Subscriber ID :');
		$orderId   = $this->ask('Enter Order Id :');
 		$company  = Company::findOrFail($companyId);
 		$srsSupplier = Supplier::whereName(Supplier::SRS_SUPPLIER)
			->whereNull('company_id')
			->firstOrFail();
 		$companySupplier = CompanySupplier::whereCompanyId($company->id)
			->whereSupplierId($srsSupplier->id)
			->firstOrFail();
 		$srsOrder = SRSOrder::whereOrderId($orderId)
			->firstOrFail();
 		$materialList = MaterialList::findOrFail($srsOrder->material_list_id);
 		$orderDetails = $srsOrder->order_details;
 		// return if required data not present
		if(!ine($orderDetails, 'transaction_date')
			|| !ine($orderDetails, 'ship_to')
			|| !ine($orderDetails, 'bill_to')
			|| !ine($orderDetails, 'po_details')
			|| !ine($orderDetails, 'customer_contact_info')) {
 			return false;
		}
 		$financialDetails = FinancialDetail::whereCompanyId($company->id)
			->whereWorksheetId($materialList->worksheet_id)
			->whereSupplierId($srsSupplier->id)
			->get();
 		$productList = [];
 		foreach ($financialDetails as $key => $financialDetail) {
			$productList[$key]['quantity'] 			= (int)$financialDetail->quantity;
			$productList[$key]['UOM'] 				= $financialDetail->unit;
			$productList[$key]['price'] 			= $financialDetail->unit_cost;
			$productList[$key]['itemCode'] 			= $financialDetail->product_code;
			$productList[$key]['itemSize'] 			= $financialDetail->size ? $financialDetail->size : '';
			$productList[$key]['itemDescription'] 	= $financialDetail->product_name ? $financialDetail->product_name : '';
			$productList[$key]['customerItem'] 		= $financialDetail->product_name ? $financialDetail->product_name : '';
		}
 		if(empty($productList)) return false;
 		$data = [
			'sourceSystem' 			=> config('srs.source_system'),
			'customerCode' 			=> $companySupplier->srs_account_number,
			'branchId' 				=> $companySupplier->branch_id,
			'shipToSequenceNumber' 	=> $companySupplier->ship_to_sequence_number,
			'accountNumber' 		=> $companySupplier->srs_account_number,
			'transactionID' 		=> $orderId,
			'transactionDate' 		=> $orderDetails['transaction_date'],
			'shipTo' 				=> $orderDetails['ship_to'],
			'billTo' 				=> $orderDetails['bill_to'],
			'poDetails' 			=> $orderDetails['po_details'],
			'customerContactInfo' 	=> $orderDetails['customer_contact_info'],
			'orderLineItemDetails' 	=> $productList,
		];
 		if (ine($orderDetails, 'notes')) {
			$data['notes'] = $orderDetails['notes'];
		}
 		$request = new Client;
		$headers = [
			'client_id'     => config('srs.client_id'),
			'client_secret' => config('srs.client_sec'),
			'guid'			=> $orderId,
		];
 		$request->setDefaultOption('headers', $headers);
 		$response = $request->post(config('srs.submit_order_url'), ['json' => $data]);
 		$this->info('Order submitted successfully.');
	}
}