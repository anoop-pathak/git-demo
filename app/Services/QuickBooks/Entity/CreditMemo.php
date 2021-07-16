<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\CustomerRepository;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\Credits\JobCredits;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use QuickBooksOnline\API\Facades\Payment as QBOPayment;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\Item as QBItem;
use Illuminate\Support\Facades\Log;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\InvoicePayment;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\JobCredit;
use App\Services\QuickBooks\Exceptions\CreditMemoNotSyncedException;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use Carbon\Carbon;
use App\Services\QuickBooks\QboDivisionTrait;
use App\Models\JobFinancialCalculation;
use App\Models\JobPaymentLine;
use App\Models\Job;
use Illuminate\Support\Facades\Auth;

class CreditMemo
{
	use QboDivisionTrait;

    public function __construct(
		CustomerRepository $customerRepo,
		JobCredits $jobCredits
	) {
		$this->customerRepo = $customerRepo;
		$this->jobCredits = $jobCredits;
	}

    public function create($qbId)
    {

		try {

			DB::beginTransaction();

			$jpCreditMemo = QuickBooks::getJobCreditByQBId($qbId);
			$job = null;

			$parentCustomerId = null;

			// If Credit memo already exists the just return.
			if($jpCreditMemo) {
				DB::commit();
				return $jpCreditMemo;
				// throw new Exception("CreditMemo already exists.");
			}

			$response = QBCreditMemo::get($qbId);

			if(!ine($response, 'entity')) {
				throw new Exception("CreditMemo not found on QuickBooks");
			}

			$enity = $response['entity'];

			$creditMemo = QuickBooks::toArray($enity);

			$customerReponse = QBCustomer::get($creditMemo['CustomerRef']);

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

			if (!$jpCustomer) {
				throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
			}

			if (!$job) {
				$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			}

			if (!$job) {
				throw new Exception("Unable to find job in JobProgress.");
			}

			$creditMemoMapped = $this->jobCreditReverseMap($job, $creditMemo);

			$jobCredit = $this->jobCredits->saveCredit($creditMemoMapped, $job);

			if($jobCredit->qb_division_id){
				$division =  QuickBooks::getDivisionByQBId($jobCredit->qb_division_id);

				if($division){
					$this->updateJobDivision($job, $division->id);
				}
			}

			DB::commit();

			JobFinancialCalculation::updateFinancials($jobCredit->job_id);

			return $jobCredit;

		} catch (Exception $e) {
			DB::rollback();
			Log::error('Unable to Create CreditMemo', [(string) $e]);

			throw $e;
		}
	}

    public function update($qbId)
    {
		try {
			DB::beginTransaction();

			$jpCreditMemo = QuickBooks::getJobCreditByQBId($qbId);

			$job = null;

			$parentCustomerId = null;

			if (!$jpCreditMemo) {
				throw new CreditMemoNotSyncedException(['credit_memo_id' => $qbId]);
			}

			$response = QBCreditMemo::get($qbId);

			if(!ine($response, 'entity')) {
				throw new Exception("Unable find Job Credit in QuickBooks");
			}

			$enity = $response['entity'];

			$qbCreditMemo = QuickBooks::toArray($enity);

			// Stop duplicate updates and webhook loop
			if($qbCreditMemo['SyncToken'] <= $jpCreditMemo->quickbook_sync_token) {
				DB::commit();
				return $jpCreditMemo;

				// throw new Exception("Job Credit is already updated in JobProgress");
			}

			$customerReponse = QBCustomer::get($qbCreditMemo['CustomerRef']);

			if(!ine($customerReponse, 'entity')) {
				throw new Exception("Unable find Customer in QuickBooks");
			}

			$customer = QuickBooks::toArray($customerReponse['entity']);

			$job = QBCustomer::getJob($customer);

			if (!$job && $customer['Job'] == 'true') {
				throw new JobNotSyncedException("Job not synced with Job Progress.", 103, null, [
					'job_id' => $customer['Id']
				]);
			}

			$isMultiJob = QBCustomer::getProjects($customer['Id']);

			$parentCustomerId = QBCustomer::getParentCustomerId($customer);

			$jpCustomer = $this->customerRepo->getByQBId($parentCustomerId);

			if (!$jpCustomer) {
				throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
			}

			if (!$job) {
				$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			}

			if (!$job) {
				throw new Exception("Unable to find job in JobProgress.");
			}

			$jobCredit = $this->updateJobCreditCommon($job, $qbCreditMemo, $jpCreditMemo->id);

			if($jobCredit->qb_division_id){
				$division =  QuickBooks::getDivisionByQBId($jobCredit->qb_division_id);

				if($division){
					$this->updateJobDivision($job, $division->id);
				}
			}

			DB::commit();

			JobFinancialCalculation::updateFinancials($jobCredit->job_id);
			return $jobCredit;

		} catch (Exception $e) {

			DB::rollback();

			Log::error('Unable to Update CreditMemo', [(string) $e]);

			throw $e;
		}
	}

    public function delete($qbId)
    {
        $jpCreditMemo = QuickBooks::getJobCreditByQBId($qbId);

		if(!$jpCreditMemo) {
			throw new Exception("Credit Memo is not synced with JobProgress.");
		}

		$job = $jpCreditMemo->job;

		if(!$job || ($job && !$job->quickbook_id)) {
			return;
		}

		DB::beginTransaction();

		try {
			$this->jobCredits->qboCancelJobCredit($jpCreditMemo);

			$jpCreditMemo->update([
				'quickbook_id'   => null,
				'quickbook_sync' => false,
			]);

			DB::commit();

		} catch(Exception $e) {

			DB::rollback();

			Log::error('Unable to Delete CreditMemo', [(string) $e]);

			throw $e;
		}
    }

    public function get($qbId)
    {
        return QuickBooks::findById('creditmemo', $qbId);
    }

	/**
	 * Update Job credit with Job and QuickBooks Credit Memo
	 */
	public function updateJobCreditCommon($job, $creditMemo, $jpId)
	{
		$creditMemoMapped = $this->jobCreditReverseMap($job, $creditMemo);

		$creditMemoMapped['id'] = $jpId;

		$jobCredit = JobCredit::where('id', $jpId)
			->first();

		if($creditMemo['SyncToken'] > $jobCredit->quickbook_sync_token) {

			$this->jobCredits->updateJobCredit($creditMemoMapped, $job);

			$lines = JobPaymentLine::where('line_type', 'credit_memo')
				->where('customer_id', $job->customer_id)
				->where('company_id', getScopeId())
				->where('quickbook_id', $creditMemo['Id'])
				->get();

			if(!$lines->isEmpty()) {

				foreach($lines as $line) {

					$jobPayment = JobPayment::where('id', $line->job_payment_id)->first();

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
			}
		}

		return $jobCredit;
	}

    /**
	 * Payment Data Map
	 */
	public function jobCreditReverseMap($job, $creditMemo)
	{

		$mapInput = [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'company_id' => getScopeId(),
			'amount' => $creditMemo['TotalAmt'],
			'quickbook_id' => $creditMemo['Id'],
			'quickbook_sync_token' => $creditMemo['SyncToken'],
			'quickbook_sync' => true,
			'unapplied_amount' => $creditMemo['RemainingCredit'],
			'date' => $creditMemo['TxnDate'],
			'qb_division_id' => $creditMemo['DepartmentRef'],
		];

		return $mapInput;
	}


	/**
	 * Create Credit Note
	 * @param  Instance $creditNote CreditNote
	 * @param  String   Credit Note Description
	 * @return Credit Note
	 */

	public function createCreditNote($creditNote, $description, $jobQuickbookId = null)
	{
		$divisionId = null;
		$job = $creditNote->job;
		$division = $job->division;
		if($job->isProject()) {
			$parentJob = Job::find($job->parent_id);
			$division  = $parentJob->division;
		}

		if($division && $division->qb_id) {
			$divisionId = $division->qb_id;
		}

		$description = substr($description , 0, 4000);
		Log::info('CreditMemo:createCreditNote', [$creditNote->id]);
		try {
			if(!$jobQuickbookId) {

				Log::info('CreditMemo:getJobQuickbookId', [$creditNote->id]);

				$jobQuickbookId = QBCustomer::getJobQuickbookId($job);
			}

			$itemRef = QBItem::findOrCreateItem();

	  		$params = [
	  			'Line' =>  [
	  				[
	  					'Amount'      => $creditNote->amount,
	  					'DetailType'  => 'SalesItemLineDetail',
	  					'Description' => $description,
	  					'SalesItemLineDetail' => [
		  					'ItemRef' => [
		  						'value' => $itemRef['id'],
		  						'name'  => $itemRef['name']
		  					]
	  					]
	  				]
	  			],
	  			'CustomerRef' => [
	  				'value' => $jobQuickbookId
	  			],
	  			'DocNumber' => $creditNote->id
			];

			if($creditNote->quickbook_id) {
				$param = [
					'query' => "SELECT *  FROM CreditMemo WHERE Id = '".$creditNote->quickbook_id."' "
				];

				$queryResponse = QuickBooks::getDataByQuery($param['query']);

				if(!empty($queryResponse)
					&& gettype($queryResponse) == 'array'
					&& $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPCreditMemo) {

					$existingCreditMemo = $queryResponse[0];
					$params['Id']        = $queryResponse[0]->Id;
					$params['SyncToken'] = $queryResponse[0]->SyncToken;
				}

				$params['Balance'] = $creditNote->unapplied_amount;
			}

			if($creditNote->date) {
	  			$params['TxnDate'] = $creditNote->date;
			}

			$params['TxnDate'] = $creditNote->date;

			if($divisionId) {
				$params['DepartmentRef']['value'] = $divisionId;
			}

			$params['PrivateNote'] = 'Created on JobProgress';

			if(ine($params, 'Id')) {

				$qbCreditMemo = \QuickBooksOnline\API\Facades\CreditMemo::update($existingCreditMemo, $params);

				$qbCreditMemo = QuickBooks::getDataService()->Update($qbCreditMemo);

				$creditNote->update([
					'quickbook_id'   => $qbCreditMemo->Id,
					'quickbook_sync_token' => $qbCreditMemo->SyncToken,
					'quickbook_sync' => true,
				]);

			} else {
				$qbCreditMemo = \QuickBooksOnline\API\Facades\CreditMemo::create($params);

				$qbCreditMemo = QuickBooks::getDataService()->Add($qbCreditMemo);

				$creditNote->update([
					'quickbook_id'   => $qbCreditMemo->Id,
					'quickbook_sync_token' => $qbCreditMemo->SyncToken,
					'quickbook_sync' => true,
				]);
			}
			$invoices = $creditNote->invoices;
			if(!$invoices->isEmpty()){
				$creditIds[] =$creditNote->id;
				$this->syncCredits($creditIds, $jobQuickbookId);
			}

			return $creditNote;
	  	} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}


	/**
	 * Delete Credit Note
	 * @param  Instance $jobCredit Job Credit
	 * @return Boolean
	 */
	public function deleteCreditNote($jobCredit)
	{
		try {

			$param = [
				'query' => "SELECT *  FROM CreditMemo WHERE Id = '".$jobCredit->quickbook_id."' "
			];

			$queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(!empty($queryResponse)
				&& gettype($queryResponse) == 'array'
				&& $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPCreditMemo) {
				$qboCreditMemo = $queryResponse[0];
				QuickBooks::getDataService()->Delete($qboCreditMemo);
			}

			if(!empty($queryResponse)){
				$data = [
					'company_id' => $jobCredit->company_id,
					'customer_id' => $jobCredit->customer_id,
					'job_id' => $jobCredit->job_id,
					'qb_customer_id' => $queryResponse[0]->CustomerRef,
					'data' => json_encode($queryResponse),
					'object' => 'CreditMemo',
					'created_by' => Auth::user()->id,
					'credit_id' => $jobCredit->id,
					'qb_credit_id' => $queryResponse[0]->Id,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString(),
				];

				DB::table('deleted_quickbook_credits')->insert($data);
			}

			$jobCredit->update([
				'quickbook_id'   => null,
				'quickbook_sync' => false,
			]);

			return true;

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}


	/**
	 * @method create customer credits on quickbook and check they are exist on quickbook online
	 * @param  \Customer $customer [description]
	 * @return [void]              [description]
	 */
	public function syncCredits($creditsIds, $referenceId)
	{
		if(empty(array_filter((array)$creditsIds))) return true;

		if(!$referenceId) return false;

		goto batch;

		batch: {
			JobPayment::whereIn('credit_id', (array)$creditsIds)
				->whereNull('ref_id')
				->whereNull('canceled')->update([
				'quickbook_sync' => false
			]);

			$query = JobPayment::whereIn('credit_id', (array)$creditsIds)
				->whereNull('ref_id')
				->whereNull('canceled');

			$query->chunk(30, function($payments) use ($referenceId) {
				$paymentData = [];
				foreach ($payments as $key => $payment) {

					$invoicePayment = InvoicePayment::wherePaymentId($payment->id)->first();
					$invoice = JobInvoice::find($invoicePayment->invoice_id);

					if(!$invoice->quickbook_invoice_id){
						continue;
					}

					$invoiceData = [];
					if($invoicePayment){
						$invoiceData['amount'] = $invoicePayment->amount;
						$invoiceData['invoice_id'] = $invoicePayment->invoice_id;
					}
					// map payment data for batch request
					$data = $this->creditPaymentByBatchRequset(
						$payment, $referenceId, $invoiceData
					);
				 	$paymentData[$key]['entity'] = 'Payment';
					$paymentData[$key]['data'] = \QuickBooksOnline\API\Facades\Payment::create($data);
					$paymentData[$key]['bId'] = $payment->id;
					$paymentData[$key]['operation'] = 'create';
				}
				$batchData['BatchItemRequest'] = $paymentData;

				try {

					$response =  QuickBooks::batchRequest($batchData);

					if(($response) && ! empty($response)) {

						foreach ($response as $key => $batchItem) {

							$payment = JobPayment::find($batchItem->batchItemId);

							if($batchItem->exception) {

								$paymentResponse = [
									'quickbook_sync' => true,
								];

							} else {
								$paymentResponse = [
									'quickbook_id'   => $batchItem->entity->Id,
									'quickbook_sync_token'   => $batchItem->entity->SyncToken,
									'quickbook_sync' => true,
								];
							}
							$payment->update($paymentResponse);
						}
					}
				} catch(Exception $e) {
					return false;
				}
			});
		}

		$pendingCount = JobPayment::whereIn('credit_id', (array)$creditsIds)
			->whereQuickbookSync(false)
			->whereNull('ref_id')
			->whereNull('canceled')
			->count();

		if($pendingCount) {
			$creditsIds = JobPayment::whereIn('credit_id', (array)$creditsIds)
				->whereNull('ref_id')
				->whereQuickbookSync(false)
				->whereNull('canceled')
				->pluck('id')
				->toArray();

			goto batch;
		}

		return true;
	}

	/**
	 * @param  Instance $payment        Payment
	 * @param  Int   	$jobQuickbookId Quickbook Id
	 * @return Mapped Credit Payment Data
	 */
	public function creditPaymentByBatchRequset($payment, $referenceId, $data) {
		$data = $this->invoiceCreditPaymentDataMap($payment, $referenceId, $data);

		return $data;
	}

	/**
	 * Payment Data Map
	 * @param  Object   $token          Token
	 * @param  Instance $payment        Job Payment
	 * @param  Int      $jobQuickbookId Quickbook Id
	 * @return Payment Data
	 */
	public function invoiceCreditPaymentDataMap($payment, $referenceId, $data)
	{
		if($payment->quickbook_id) {

			$param = [
				'query' => "SELECT *  FROM Payment WHERE Id = '".$payment->quickbook_id."'"
			];

			$queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(!empty($queryResponse)) {
				$payment->quickbook_id         = $queryResponse[0]->Id;
				$payment->quickbook_sync_token = $queryResponse[0]->SyncToken;
				$payment->update();
			} else {

				$payment->update([
					'quickbook_id'         => null,
					'quickbook_sync_token' => false,
				]);
			}
		}

		$customerId = $payment->customer_id;

		$mapInput = [
			'CustomerRef' => [
				'value' => $referenceId,
			],
			'TotalAmt' => 0,
			'sparse' => true,
		];

		if($payment->quickbook_id) {
			$mapInput['Id']        = $payment->quickbook_id;
			$mapInput['SyncToken'] = $payment->quickbook_sync_token;
		}

		$data['method'] = $payment->method;

		if($payment->method === 'echeque') {
			$data['method'] = 'Check';
		}

		if($payment->unapplied_amount) {
			$mapInput['UnappliedAmt'] = 0;
		}

		$invoice = JobInvoice::find($data['invoice_id']);

		$credit = $payment->credit;

		$quickbookInvoice = QBInvoice::getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);

		$quickbookCreditMemo = $this->get($credit->quickbook_id);

		$txnInvoiceData[0] = [
			'TxnId'   => $quickbookInvoice->Id,
		    'TxnType' => "Invoice"
		];

		$LinkedInvoiceTxn = [
			'LinkedTxn' => $txnInvoiceData,
			'Amount' => number_format($data['amount'], 2, '.', '')
		];

		$txnCreditData[0] = [
			'TxnId'   => $quickbookCreditMemo['entity']->Id,
		    'TxnType' => "CreditMemo"
		];

		$LinkedCreditTxn = [
			'LinkedTxn' => $txnCreditData,
			'Amount' => number_format($data['amount'], 2, '.', '')
		];

		$mapInput['Line'][0] = $LinkedInvoiceTxn;
		$mapInput['Line'][1] = $LinkedCreditTxn;

		$payentMethodRefId = QBPayment::getPaymentReference(ucfirst($data['method']));

		if($payentMethodRefId) {
			$mapInput['PaymentMethodRef']['value'] = $payentMethodRefId;
		}

		if($payment->echeque_number) {
			$mapInput['PaymentRefNum'] =  $payment->echeque_number;
		}

		if($payment->date) {
			$mapInput['TxnDate'] = $payment->date;
		}

		$mapInput['PrivateNote'] = 'Created on JobProgress';

		return $mapInput;
	}

	/**
	 * Invoice Payment
	 * @param  Object   $token Token
	 * @param  Instance $payment JobPayment
	 * @return Payment Instance
	 */
	public function invoiceCreditPaymentSync($payment, $referenceId, $data)
	{
		try {
			$paymentData = $this->invoiceCreditPaymentDataMap($payment, $referenceId, $data);

			if(ine($paymentData, 'Id')) {
				$response = QBPayment::get($paymentData['Id']);

				$resultingPayment = $response['entity'];

				$qboPayment = QBOPayment::update($resultingPayment, $paymentData);

				$resultingPayment = QuickBooks::getDataService()->Add($qboPayment);

			} else {

				$qboPayment = QBOPayment::create((array) $paymentData);

				$resultingPayment = QuickBooks::getDataService()->Add($qboPayment);
			}

			$payment->update([
				'quickbook_id'         => $resultingPayment->Id,
				'quickbook_sync_token' => $resultingPayment->SyncToken,
				'quickbook_sync'       => true
			]);

			return $payment;

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	public function getJobCredit($qbId, $meta = [])
	{
		$jobCredit = QuickBooks::getJobCreditByQBId($qbId);

		return $jobCredit;
	}

	public function isCustomerAccountSynced($id, $origin){
		if($origin){
			$jobCredit = JobCredit::where('company_id', getScopeId())->where('quickbook_id', $id)->first();
		}else{
			$jobCredit = JobCredit::where('company_id', getScopeId())->whereId($id)->first();
		}

		if(!$jobCredit)return false;

		$customer = $jobCredit->customer;

		return (bool)$customer->quickbook_id;
	}

	/**
	 * Get Quickbook Pdf File
	 * @param  JobCredit $jobCredit
	 * @return Pdf Format File | false
	 */
	public function getPdf(JobCredit $jobCredit)
	{
		$result = false;
		try {
			$response = $this->get($jobCredit->quickbook_id);

			$creditMemo = $response['entity'];

			if(!empty($creditMemo)) {

				$creditMemoObj = \QuickBooksOnline\API\Facades\CreditMemo::create([
					"Id" => (string) $creditMemo->Id
				]);

				$pdf = QuickBooks::getDataService()->DownloadPDF($creditMemoObj);

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
}