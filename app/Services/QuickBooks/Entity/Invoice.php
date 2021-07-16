<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\QuickBookRepository;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBooks\QuickBookService;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Services\JobInvoices\JobInvoiceService;
use App\Models\JobInvoice;
use Illuminate\Support\Facades\DB;
use App\Models\Job;
use FlySystem;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Services\QuickBooks\Exceptions\InvoiceNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Models\QuickBookTask;
use Carbon\Carbon;
use Settings;
use App\Services\QuickBooks\QboDivisionTrait;
use App\Models\CustomTax;

class Invoice
{
	use QboDivisionTrait;

    public function __construct(
		QuickBookRepository $repo,
		CustomerRepository $customerRepo,
		QuickBookService $quickbooksService,
		JobInvoiceService $invoiceService
	) {
		$this->repo         = $repo;
		$this->customerRepo = $customerRepo;
		$this->quickbooksService = $quickbooksService;
		$this->invoiceService = $invoiceService;

	}

	public function create($id)
    {

		try {

			DB::beginTransaction();

			$job = null;

			$response = $this->get($id);

			if(!ine($response, 'entity')) {
				throw new Exception("Invoice not found on QuickBooks");
			}

			$entity = $response['entity'];

			$invoice = QuickBooks::toArray($entity);

			$jpInvoice = QuickBooks::getJobInvoiceByQBId($id);

			if($jpInvoice) {
				//If Invoice already exists than return invoice object
				DB::commit();
				return $jpInvoice;
				// throw new Exception("Invoice already exists.");
			}

			$customerReponse = QBCustomer::get($invoice['CustomerRef']);

			if(!ine($customerReponse, 'entity')) {
				throw new Exception("Customer not found on QuickBooks");
			}

			$customer = QuickBooks::toArray($customerReponse['entity']);

			$job = QBCustomer::getJob($customer);

			if (!$job && $customer['Job'] == 'true') {
				throw new JobNotSyncedException(['job_id' => $customer['Id']]);
			}

			$isMultiJob = QBCustomer::getProjects($customer['Id']);//need to refactor this(auhtor:Anoop)

			$parentCustomerId = QBCustomer::getParentCustomerId($customer);

			$jpCustomer = $this->customerRepo->getByQBId($parentCustomerId);

			if(!$jpCustomer) {
				throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
			}

			if (!$job) {
				$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			}

			if (!$job) {
				throw new Exception("Unable to find job in JobProgress.");
			}

			$invoiceMapped = $this->jobInvoiceReverseMap($job, $invoice);

			$validator = Validator::make($invoiceMapped, JobInvoice::getRules());

			if($validator->fails()) {
				throw new Exception("Validation Failed Invoice can't be created.". json_encode($validator->failed()));
			}

			$invoiceSaved = $this->invoiceService->saveInvoice($job, $invoiceMapped['lines'], $invoiceMapped);

			$invoiceSaved->update([
				'quickbook_invoice_id' => $invoice['Id'],
				'quickbook_sync' => true,
				'quickbook_sync_token' => $invoice['SyncToken'],
				'origin' => QuickBookTask::ORIGIN_QB,
				'last_updated_origin' => QuickBookTask::ORIGIN_QB,
			]);

			if($invoiceSaved->qb_division_id){
				$division =  QuickBooks::getDivisionByQBId($invoiceSaved->qb_division_id);

				if($division){
					$this->updateJobDivision($job, $division->id);
				}
			}

			DB::commit();

			$this->invoiceService->updateJobPrice($invoiceSaved, $invoiceMapped);

			return $invoiceSaved;

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to Create Invoice', [(string) $e]);

			throw $e;
		}
    }

    public function update($id)
    {
		try {

			DB::beginTransaction();

			$jpInvoice = QuickBooks::getJobInvoiceByQBId($id);

			if(!$jpInvoice) {
				throw new InvoiceNotSyncedException(['invoice_id' => $id]);
			}

			$response = $this->get($id);

			if(!ine($response, 'entity')) {
				throw new Exception("Invoice is not found in QuickBooks.");
			}

			$enity = $response['entity'];

			$invoice = QuickBooks::toArray($enity);

			// Stop duplicate updates and webhook loop
			if($invoice['SyncToken'] <= $jpInvoice->quickbook_sync_token) {
				throw new Exception("Invoice already updated.");
			}

			$customerReponse = QBCustomer::get($invoice['CustomerRef']);

			if(!ine($customerReponse, 'entity')) {
				throw new Exception("Customer not found on QuickBooks");
			}

			$customer = QuickBooks::toArray($customerReponse['entity']);

			$job = QBCustomer::getJob($customer);

			if (!$job && $customer['Job'] == 'true') {
				throw new JobNotSyncedException(['job_id' => $customer['Id']]);
			}

			$isMultiJob = QBCustomer::getProjects($customer['Id']);

			$parentCustomerId = QBCustomer::getParentCustomerId($customer);

			$jpCustomer = $this->customerRepo->getByQBId($parentCustomerId);

			if(!$jpCustomer) {
				throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
			}

			if (!$job) {
				$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			}

			if (!$job) {
				throw new Exception("Unable to find job in JobProgress.");
			}

			$invoiceMapped = $this->jobInvoiceReverseMap($job, $invoice);

			$validator = Validator::make($invoiceMapped, JobInvoice::getRules());

			if($validator->fails()) {
				throw new Exception("Validation Failed. Invoice can't be updated.");
			}

			$invoiceMapped['id'] = $jpInvoice['id'];

			$invoiceSaved = $this->invoiceService->qbUpdateJobInvoice($jpInvoice, $invoiceMapped['lines'], $invoiceMapped);

			$invoiceSaved->update([
				'quickbook_invoice_id'   => $invoice['Id'],
				'quickbook_sync' => true,
				'quickbook_sync_token' => $invoice['SyncToken'],
				'last_updated_origin' => QuickBookTask::ORIGIN_QB,
			]);

			$this->updateLinkedTransections($invoice);

			if($invoiceSaved->qb_division_id){
				$division =  QuickBooks::getDivisionByQBId($invoiceSaved->qb_division_id);

				if($division){
					$this->updateJobDivision($job, $division->id);
				}
			}

			DB::commit();

			$this->invoiceService->updateJobPrice($invoiceSaved, $invoiceMapped);

			return $invoiceSaved;

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to Update Invoice', [(string) $e]);

			throw $e;
		}
    }

    public function delete($id)
    {
		$jpInvoice = QuickBooks::getJobInvoiceByQBId($id);

		if(!$jpInvoice) {
			throw new Exception("Invoice is not synced with JobProgress.");
		}

		try {

			DB::beginTransaction();

			//cancel Change order if it is linked with
			$changeOrder = $jpInvoice->changeOrder;
			if($changeOrder){
				DB::table('change_orders')
					->where('change_orders.id', $changeOrder->id)
					->update(['canceled' => Carbon::now()->toDateTimeString()]);
			}

			/**
			 * Delete Invoice from JobProgress but don't try to delete it from QuickBooks because it is already deleted.
			 */
			$this->invoiceService->deleteJobInvoice($jpInvoice, false);


			DB::commit();

		} catch (Exception $e) {

			DB::rollback();

			// Log::error('Unable to Delete Invoice', [(string) $e]);

			throw $e;
		}

    }

    public function get($id)
    {
        return QuickBooks::findById('invoice', $id);
	}

	/**
	 * Update Linked transection when invoice updated
	 */
	public function updateLinkedTransections($invoice)
	{
		if(isset($invoice['LinkedTxn'])) {

			$transections = $invoice['LinkedTxn'];

			if(isset($transections[0]) && is_array($transections[0])) {

				foreach($transections as $transection) {

					if($transection['TxnType'] == 'Payment') {

						$this->updateTransection($transection['TxnId']);
					}
				}

			} else {

				if($invoice['LinkedTxn']['TxnType'] == 'Payment') {

					$this->updateTransection($invoice['LinkedTxn']['TxnId']);
				}
			}
		}
	}

	private function updateTransection($qbId)
	{
		$jobPayment = QuickBooks::getJobPaymentByQBId($qbId);

		if($jobPayment && $jobPayment->quickbook_id) {

			$response = QBPayment::get($jobPayment->quickbook_id);

			if(ine($response, 'entity')) {

				$qbPayment = $response['entity'];

				if($qbPayment->SyncToken > $jobPayment->quickbook_sync_token) {

					QBPayment::update($jobPayment->quickbook_id);
				}
			}
		}
	}

    public function jobInvoiceReverseMap($job, $invoice)
	{
		$itemDescription = $job->customer->first_name . ' ' . $job->customer->last_name . '/' . $job->number;

		$mapInput = [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'quickbook_invoice_id' => $invoice['Id'],
			'quickbook_sync_token' => $invoice['SyncToken'],
			'quickbook_sync' => true,
			'date' => $invoice['TxnDate'],
			'due_date' => $invoice['DueDate'],
			'date' => $invoice['TxnDate'],
			'note' => $invoice['CustomerMemo'],
			'qb_division_id' => $invoice['DepartmentRef'],
		];

		if(ine($invoice, 'TxnTaxDetail') && !empty($invoice['TxnTaxDetail']['TxnTaxCodeRef'])) {

			$taxCodeId = $invoice['TxnTaxDetail']['TxnTaxCodeRef'];

			$taxDetails = $this->getCustomTaxByQuickBooksId($taxCodeId);

			if(!$taxDetails) {

				QuickBooks::syncQuickBookTaxes();

				$taxDetails = $this->getCustomTaxByQuickBooksId($taxCodeId);

				if(!$taxDetails) {

					throw new Exception('Unable to apply QuickBooks tax');
				}
			}

			$mapInput['tax_rate'] = $taxDetails->tax_rate;

			$mapInput['custom_tax_id'] = $taxDetails->id;

			$mapInput['taxable'] = true;
		}

		$lineItems = [];

		$invoiceLines = $invoice['Line'];

		$hasTaxLine = false;

		$taxLineAmount = 0;

		foreach ($invoiceLines as $line) {

			if($line['DetailType'] == 'SubTotalLineDetail') {

				// add total amount without tax. as we are doing in JobProgress.
				$mapInput['amount'] = $line['Amount'];
			}

			if($line['DetailType'] == 'SalesItemLineDetail') {

				$itemRef = $line['SalesItemLineDetail']['ItemRef'];

				$item = QBItem::get($itemRef);

				$item = $item['entity'];

				if($item->Name == 'Tax') {

					$hasTaxLine = true;

					$taxLineAmount = $line['Amount'];
				}

				if(!$hasTaxLine) {

					$quantity = $line['SalesItemLineDetail']['Qty'];

					// If quantity is empty then set one
					if(!$quantity) {
						$quantity = 1;
					}

					$lineItems[] = [
						'amount' => numberFormat($line['Amount'] / $quantity, 3),
						'description' => (!empty($line['Description'])) ? $line['Description'] :  $itemDescription . '/' . $item->Name,
						'is_chargeable' => true,
						'is_taxable' => ($line['SalesItemLineDetail']['TaxCodeRef'] == 'TAX') ? true: false,
						'quantity'=> numberFormat($quantity, 3),
						'quickbook_id' => $line['Id']
					];
				}
			}
		}

		// exclude Tax line amount
		if($hasTaxLine) {

			$mapInput['amount'] = $mapInput['amount'] - $taxLineAmount;
		}

		$mapInput['lines'] = $lineItems;

		$mapInput['invoice_number'] = $this->invoiceService->getInvoiceNumber();

		$mapInput['last_updated_origin'] = QuickBookTask::ORIGIN_QB;
		return $mapInput;
	}

	private function getCustomTaxByQuickBooksId($taxCodeId)
	{
		$taxDetails = CustomTax::withTrashed()
			->where('company_id', getScopeId())
			->where('quickbook_tax_code_id', $taxCodeId)
			->first();

		if(empty($taxDetails)) {
			QuickBooks::syncQuickBookTaxes();
		}

		$taxDetails = CustomTax::withTrashed()
				->where('company_id', getScopeId())
				->where('quickbook_tax_code_id', $taxCodeId)
				->first();

		return $taxDetails;
	}

	/**
	 * @param  Object   $token   Quickbook Token
	 * @param  Instance $invoice Job Invoice
	 * @return Invoice
	 */

	public function createOrUpdateInvoice(JobInvoice $invoice)
	{
		try {
			$divisionId = null;
			$customer = $invoice->customer;
			$job      = $invoice->job;
			$jobQuickbookId = QBCustomer::getJobQuickbookId($job);

			$division = $job->division;
			if($job->isProject()) {
				$parentJob = Job::find($job->parent_id);
				$division  = $parentJob->division;
			}

			if($division && $division->qb_id) {
				$divisionId = $division->qb_id;
			}

			$settings = Settings::get('QBO_ITEMS');
			if(ine($settings, 'Services')) {
				$itemId = $settings['Services']['qb_id'];
				$itemName = 'Services';
			} else {
				$item = QBItem::findOrCreateItem();
				$itemId = $item['id'];
				$itemName = $item['name'];
			}

			$invoiceData = $this->invoiceMapData(
				$invoice,
				$jobQuickbookId,
				$itemId,
				$itemName,
				$divisionId
			);

			if(ine($invoiceData, 'Id')) {

				$existingInvoice = $this->get($invoiceData['Id']);

				if(!ine($existingInvoice, 'entity')) {
					throw new Exception('Unable to fetch invoice from quickbooks');
				}

				$qboInvoice = \QuickBooksOnline\API\Facades\Invoice::update($existingInvoice['entity'], $invoiceData);
				$quickbookInvoice = QuickBooks::getDataService()->Update($qboInvoice);
				$invoice->quickbook_invoice_id = $quickbookInvoice->Id;
				$invoice->quickbook_sync_token =  $quickbookInvoice->SyncToken;
				$invoice->quickbook_sync = true;

				$invoice->save();

			} else {

				$qboInvoice = \QuickBooksOnline\API\Facades\Invoice::create($invoiceData);
				$quickbookInvoice = QuickBooks::getDataService()->Add($qboInvoice);
				$invoice->quickbook_invoice_id = $quickbookInvoice->Id;
				$invoice->quickbook_sync_token =  $quickbookInvoice->SyncToken;
				$invoice->quickbook_sync = true;
				$invoice->save();
			}

			if(!empty($quickbookInvoice)) {
				//create or update qb invoice pdf
				$this->createOrUpdateQbInvoicePdf($invoice, $quickbookInvoice->Id);
			}

			return $invoice;

		} catch (Exception $e) {

			if(isset($invoiceData)){
				$context['data_map'] = $invoiceData;
			}
			QuickBooks::quickBookExceptionThrow($e, $context);
		}
	}

	/**
	 * Get Quickbook Pdf File
	 * @param  JobInvoice $jobInvoice
	 * @return Pdf Format File | false
	 */
	public function getPdf(JobInvoice $jobInvoice)
	{
		$result = false;

		try {
			$invoice = $this->getQuickbookInvoice($jobInvoice->quickbook_invoice_id, $jobInvoice->invoice_number);

			if(!$invoice) return $result;

			if(!empty($invoice)) {

				$invoiceObj = \QuickBooksOnline\API\Facades\Invoice::create([
					"Id" => (string) $invoice->Id
				]);

				$pdf = QuickBooks::getDataService()->DownloadPDF($invoiceObj);

				$fileSystem = app()->make('Illuminate\Filesystem\Filesystem');

				if(! $fileSystem->exists($pdf)) {
					return false;
				}

				$result = $fileSystem->get($pdf);
			}

		} catch (Exception $e) {
			return $result;
		}

		return $result;
	}

	/**
	 * create and update Qb invoice pdf file
	 * @param  Instance $invoice Job Invoice
	 */
	public function createOrUpdateQbInvoicePdf(JobInvoice $invoice, $qbInvoiceId = null) {

		// If only invoice is passed then check quickbook id in the invoice object
		if($invoice) {
			$qbInvoiceId = $invoice->quickbook_invoice_id;
		}

		// If invoice id is not present then return
		if(empty($qbInvoiceId)) {
			return false;
		}

		$reponse = $this->getPdf($invoice);

		$pdf = $reponse;

		if(!$pdf) return false;

		$fileName = $invoice->id.'_qb_invoice.pdf';

		$baseName = 'job_invoices/'.$fileName;

		$fullPath = config('jp.BASE_PATH') . $baseName;

		FlySystem::put($fullPath, $pdf, ['ContentType' => 'application/pdf']);

		$invoice->qb_file_path = $baseName;

		$invoice->qb_file_size = FlySystem::getSize($fullPath);

		$invoice->update();

		return true;
	}

	/**
	 * Invoice Map Data
	 * @param  Instance $invoice JobInvoice
	 * @param  Int      $customerReferenceId Customer Reference Id
	 * @param  Int      $serviceId   Service Id
	 * @param  String   $serviceName Service Name
	 * @return Array Invoice Map Data
	 */
	public function invoiceMapData($invoice, $customerReferenceId, $serviceId, $serviceName, $divisionId = null, $searchInvoice = true)
	{
		//create quickbook lines
		$lineItems = [];
		$discountAmt = 0;
		$invoiceData = [];
		$response = null;
		$isTaxable = false;
		$taxRate = false;
		/**
		 * To preserve the original item type with details and change only quanlity and price
		 */
		$originalLines = [];

		if($searchInvoice) {
			$response = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);
		}

		if(!empty($response)) {
			$invoiceData['Id']        = $response->Id;
			$invoiceData['SyncToken'] = $response->SyncToken;
			$invoiceData['sparse'] = true;
			$originalLines = $response->Line;
		}

		$tax = null;

		$isQuickBooksTax = null;

		if($invoice->taxable == true) {
			$isTaxable = true;

			$tax = $invoice->CustomTax;
		}

		// If quickbook tax is applied to the invoice.
		if(!empty($tax) && !empty($tax->quickbook_tax_code_id)) {

			$isQuickBooksTax = true;
		}

		/**
		 * If tax is apllied but it is not a QuickBook Tax
		 * In this case create new line item (with name 'Tax') calculate the tax from the Tax rate and add the line item with same price
		 */

		$itemId = null;

		if($isTaxable == true && !$isQuickBooksTax) {

			$settings = Settings::get('QBO_ITEMS');

			if(ine($settings, 'Tax')) {
				$itemId = $settings['Tax']['qb_id'];
				$itemName = 'Tax';
			} else {
				$taxItem = QBItem::findOrCreateItem("Tax");
				$itemId = $taxItem['id'];
				$itemName = $taxItem['name'];
			}

			$taxRate = $invoice->tax_rate;
		}

		$total = 0;

		foreach ($invoice->lines as $line) {

			$defaultServiceItem = $serviceId;

			if(isset($line->workType->qb_id)) {

				$defaultServiceItem = $line->workType->qb_id;
			}

			if(!$line->is_chargeable) {

				$discountAmt = $discountAmt + ($line->amount * $line->quantity);

				$lineAmount  = $line->amount;

			} else {

				// If normal tax is applied to the Invoice then make
				// line amount inclusive of tax as default tax on JobProgress is working
				// if(!$isQuickBooksTax) {

				// 	$lineAmount = totalAmount($line->amount, $invoice->getTaxRate());
				// } else {

				// 	$lineAmount = $line->amount;
				// }

				$lineAmount = $line->amount;
			}
			$line['description'] = substr($line['description'] , 0, 4000);
			$lineItem = [
				'Amount' => numberFormat($lineAmount * $line->quantity),
				'DetailType'  => 'SalesItemLineDetail',
				'Description' => $line['description'],
				'SalesItemLineDetail' => [
					'ItemRef' => [
						'value' => $defaultServiceItem,
					],
					'UnitPrice' => $lineAmount,
					'Qty'       => $line->quantity,
				]
			];

			if($line->is_chargeable && $invoice->taxable == true && $line->is_taxable == true && $isQuickBooksTax) {

				// Set line taxable
				// Only applicable only on US companies
				$lineItem['SalesItemLineDetail']['TaxCodeRef'] = [
					"value" => 'TAX'
				];
			}

			// If Line has quickbook reference
			if($line->quickbook_id) {

				foreach($originalLines as $orgLine) {
					/**
					 * If Item is not default line
					 * item that we have created in QB for compatibility
					 */
					if($orgLine->Id == $line->quickbook_id
						&& $lineItem['SalesItemLineDetail']['ItemRef'] != $serviceId) {
						// replace with original description
						$lineItem['SalesItemLineDetail']['ItemRef'] = $orgLine->SalesItemLineDetail->ItemRef;
					}
				}
			}

			$total = $total + $lineAmount;

			$lineItems[] = $lineItem;
		}

		$total = $total - $discountAmt;

		$taxAmount = 0;

		if($taxRate) {
			$taxAmount  = QuickBooks::getTotalTax($total, $taxRate);
		}

		if($taxRate && $taxAmount) {

			$taxLine = [
				'Amount' => $taxAmount,
				'DetailType'  => 'SalesItemLineDetail',
				'Description' => 'Tax',
				'SalesItemLineDetail' => [
					'ItemRef' => [
						'value' => $itemId,
					],
					'UnitPrice' => $taxAmount,
					'Qty'       => 1,
				]
			];

			$lineItems[] = $taxLine;
		}

		if($discountAmt) {
			$lineItems[] = [
				"DetailType" => "DiscountLineDetail",
				"Amount" => numberFormat($discountAmt),
				"DiscountLineDetail" => [
					"PercentBased" => false
				]
			];
		}

		// Not applicable to US based companies.

		//$invoiceData["GlobalTaxCalculation"] = 'TaxInclusive';

		// Add tax code for to make invoice taxable
		// If invoice is taxable in JobProgress

		if($tax && $isQuickBooksTax && $invoice->taxable == true) {

			$invoiceData["TxnTaxDetail"] = [
				"TxnTaxCodeRef" => [
					"value" => $tax->quickbook_tax_code_id,
					"name" => $tax->title
				]
			];
		}

		$invoiceData = array_merge($invoiceData, [
			'Line' => $lineItems,
			'CustomerRef' => [
				'value' => $customerReferenceId
			],
			'DocNumber' => JobInvoice::QUICKBOOK_INVOICE_PREFIX.$invoice->invoice_number
		]);

		if($divisionId) {
			$invoiceData['DepartmentRef']['value'] = $divisionId;
		}

		//Set Default Invoice Due  Date and Txn Date
		$dateTime = convertTimezone($invoice->created_at, Settings::get('TIME_ZONE'));

		$date = $dateTime->format('Y-m-d');

		$invoiceData['DueDate'] = $date;

		$invoiceData['TxnDate'] = $date;

		if($invoice->due_date) {
			$invoiceData['DueDate'] = $invoice->due_date;
		}

		if($invoice->date) {
			$invoiceData['TxnDate'] = $invoice->date;
		}

		if($invoice->note) {
			$invoiceData['CustomerMemo']['value'] = $invoice->note;
		}

		return $invoiceData;
	}

	/**
	 * Get Quickbook Invoice Data By Id
	 * @param  Int $id Invoice Id
	 * @return Array Of Invoice
	 */
	public function getQuickbookInvoice($quickbookInvoiceId, $invoiceNumber)
	{
		$response = [];

		if($quickbookInvoiceId) {

			$param = [
				'query' => "SELECT *  FROM Invoice WHERE Id = '".$quickbookInvoiceId."'"
			];

			$response = QuickBooks::getDataByQuery($param['query']);

			if(QuickBooks::isValidResponse($response, '\QuickBooksOnline\API\Data\IPPInvoice')) {
				return $response[0];
			}
		}

		//commented By Anoop
		//Because it gives error when we resynch same invoice on QBO if it's customer is inactive.

		// if(empty($response)) {

		// 	$param = [
		// 		'query' => "SELECT *  FROM Invoice WHERE DocNumber = '".JobInvoice::QUICKBOOK_INVOICE_PREFIX.$invoiceNumber."'"
		// 	];
		// 	$response = QuickBooks::getDataByQuery($param['query']);

		// 	if(QuickBooks::isValidResponse($response, '\QuickBooksOnline\API\Data\IPPInvoice')) {
		// 		return $response[0];
		// 	}
		// }

		return $response;
	}

	/**
	 * Delete Invoice from QuickBook
	 * @param  Instance $jobCredit Job Credit
	 * @return Boolean
	 */
	public function deleteJobInvoice($jobInvoice)
	{

		try {

			if(!$jobInvoice->quickbook_invoice_id) return false;

			$qbInvoice = $this->getQuickbookInvoice($jobInvoice->quickbook_invoice_id, $jobInvoice->invoice_number);

			if($qbInvoice) {

				$resultingObj =QuickBooks::getDataService()->Delete($qbInvoice);

				$jobInvoice->update([
					'quickbook_invoice_id'  => null,
					'quickbook_sync_token'  => null,
					'quickbook_sync'        => false,
				]);

				$data = [
					'company_id' => getScopeId(),
					'customer_id' => $jobInvoice->customer_id,
					'job_id' => $jobInvoice->job_id,
					'qb_customer_id' => $qbInvoice->CustomerRef,
					'data' => json_encode($qbInvoice),
					'object' => 'Invoice',
					'created_by' => Auth::user()->id,
					'invoice_id' => $jobInvoice->id,
					'qb_invoice_id' => $qbInvoice->Id,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString(),
				];

				DB::table('deleted_quickbook_invoices')->insert($data);

				return true;
			}

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Sync all Job Invoice
	 * @param  Job Instance $job   Job
	 * @return Boolean
	 */
	public function syncPendingCustomerInvoices($customer)
	{
		try {
			$jobids = $customer->jobs->pluck('id')->toArray();

			$count  = JobInvoice::whereIn('job_id', (array)$jobids)
				->whereNull('quickbook_invoice_id')
				->count();
			if(!$count) return false;

			$meta = QBItem::findOrCreateItem();
			goto batch;
			batch:{
				$query = JobInvoice::whereIn('job_id', (array)$jobids)
							->with('job.division')
							->whereNull('quickbook_invoice_id');

				$query->chunk(30, function($invoices) use($meta)
				{
					$invoiceData = [];
					foreach ($invoices as $key => $invoice) {
						$response = $this->getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);

						if(!empty($response)) {
							$invoice->quickbook_invoice_id = $response['Id'];
							$invoice->quickbook_sync_token = $response['SyncToken'];
							$invoice->save();
							continue;
						}

						$job = $invoice->job;
						$customer = $job->customer;
						$jobQuickbookId = QBCustomer::getJobQuickbookId($job);
						$division = $job->division;

						if($job->isProject()) {
							$parentJob = Job::find($job->parent_id);
							$division  = $parentJob->division;
						}
						$divisionId = null;

						if($division && $division->qb_id) {
							$divisionId = $division->qb_id;
						}

						$data = $this->invoiceMapData(
							$invoice,
							$jobQuickbookId,
							$meta['id'],
							$meta['name'],
							$divisionId,
							$searchInvoice = false
						);

						$paymentData[$key]['entity'] = 'Invoice';
						$invoiceData[$key]['data']   = $data;
						$invoiceData[$key]['bId']       = $invoice->id;
						$invoiceData[$key]['operation'] = 'create';

					}

					if(empty($invoiceData)) return false;

					$batchData['BatchItemRequest'] = $invoiceData;

					$response = QuickBooks::batchRequest($batchData);

					if(($response) && ! empty($response)) {

						foreach ($response as $key => $batchItem) {

							$jobInvoice = JobInvoice::find($batchItem->batchItemId);

							if($batchItem->exception) {

								$qbInvoice = $this->createOrUpdateInvoice($jobInvoice);

							} else {

								$jobInvoice->update([
									'quickbook_invoice_id' => $batchItem->entity->Id,
									'quickbook_sync_token'   => $batchItem->entity->SyncToken,
									'quickbook_sync' => true
								]);
							}
						}
					}
				});
			}

			$count =  JobInvoice::whereIn('job_id', (array)$jobids)
				->whereNull('quickbook_invoice_id')
				->count();

			if($count)	{
				goto batch;
			}

			return true;
		} catch(Exception $e) {
			if($e->getCode() != 429) {
				QuickBooks::quickBookExceptionThrow($e);
			}
		}
	}

	/**
	 * Create or Return new Invoice in JobProgress
	 */

	public function getInvoice($qbId, $meta = [])
	{
		$invoice = QuickBooks::getJobInvoiceByQBId($qbId);

		return $invoice;
	}

	/**
	 * Sycn changes for Quickbook changes on periodic basis
	 * @param Number $interval
	 * Interval in minutes default 10 minutes
	 */

	public function syncQuicbookChanges($interval = 10)
	{
		try {

			$response = QuickBooks::cdc(['invoice'], $interval);

			if ($response->entities && ine($response->entities, 'Invoice')) {

				$invoices = $response->entities['Invoice'];

				foreach ($invoices as $invoice) {

					$jobInvoice = JobInvoice::where('quickbook_invoice_id', $invoice->Id)->first();

					if($jobInvoice
						&& $jobInvoice->quickbook_id
						&& $invoice->SyncToken > $jobInvoice->quickbook_sync_token) {

						$this->update($invoice->Id);
					}
				}
			}

		} catch (Exception $e) {

			throw new Exception($e);
		}
	}

	public function isCustomerAccountSynced($id, $origin){
		if($origin){
			$jobInvoice = JobInvoice::where('quickbook_invoice_id', $id)->first();
		}else{
			$jobInvoice = JobInvoice::whereId($id)->first();
		}

		if(!$jobInvoice) return false;

		$customerId = $jobInvoice->customer_id;

		$customer = Customer::where('company_id', getScopeId())->where('id', $customerId)->first();

		if(!$customer) return false;

		return (bool)$customer->quickbook_id;
	}
}