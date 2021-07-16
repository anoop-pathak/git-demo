<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\QuickBookRepository;
use App\Repositories\CustomerRepository;
use App\Services\FinancialDetails\FinancialPayment;
use App\Repositories\PaymentMethodsRepository;
use Illuminate\Support\Facades\DB;
use App\Repositories\JobPaymentsRepository;
use QuickBooksOnline\API\Facades\Payment as QBOPayment;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\PaymentMethod as QBPaymentMethod;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use Exception;
use App\Models\JobPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\QuickBooks\Exceptions\JobNotSyncedException;
use App\Services\QuickBooks\Exceptions\ParentCustomerNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentLineNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentMethodNotSyncedException;
use App\Services\QuickBooks\Exceptions\PaymentNotSyncedException;
use Carbon\Carbon;
use App\Models\QuickBookTask;
use App\Models\JobFinancialCalculation;

class Payment
{
    public function __construct(
		QuickBookRepository $repo,
		CustomerRepository $customerRepo,
		FinancialPayment $financialPayment,
		PaymentMethodsRepository $paymentMethodsRepo,
		JobPaymentsRepository $jobPaymentsRepo
	) {
		$this->repo         = $repo;
		$this->customerRepo = $customerRepo;
		$this->financialPayment = $financialPayment;
		$this->paymentMethodsRepo = $paymentMethodsRepo;
		$this->jobPaymentsRepo = $jobPaymentsRepo;
	}

    public function create($qbId)
    {
		try {

			$jpPayment = QuickBooks::getJobPaymentByQBId($qbId);

			// If payment already exists the just return.
			if($jpPayment) {
				return $jpPayment;
				// throw new Exception("Payment already exists.");
			}

			$response = $this->get($qbId);

			if(!ine($response, 'entity')) {
				throw new Exception("Payment not found on QuickBooks");
			}
			
			$qbPayment = $response['entity'];

			$payment = QuickBooks::toArray($qbPayment);

			$customerReponse = QBCustomer::get($payment['CustomerRef']);
			
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
			
			if(!$job) {
				$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			}

			if (!$job) {
				throw new Exception("Unable to find job in JobProgress.");
			}

			$lines = QBPayment::getUnSyncedLines($payment);

			if(!empty($lines)) {

				throw new PaymentLineNotSyncedException($lines);
			}

			$paymentMapped = $this->jobPaymentReverseMap($job, $payment);

			$jpPayment = $this->jobPayment($paymentMapped, $payment);

			$jpPayment->origin = QuickBookTask::ORIGIN_QB;

			return $jpPayment;

		} catch (Exception $e) {

			Log::error('Unable to Create Payment', [(string) $e]);

			throw $e;
		}

    }
    
    public function update($qbId)
    {

		try {

			DB::beginTransaction();

			$response = $this->get($qbId);

			if(!ine($response, 'entity')) {
				throw new Exception("Payment not found on QuickBooks");
			}

			$entity = $response['entity'];

			$payment = QuickBooks::toArray($entity);

			$jpPayment = QuickBooks::getJobPaymentByQBId($payment['Id']);

			//If payment updated but not linked with JobProgress.
			if(!$jpPayment) {
				throw new PaymentNotSyncedException(['payment_id' => $qbId]);
			}

			if($payment['SyncToken'] <= $jpPayment->quickbook_sync_token) {
				DB::commit();
				return $jpPayment;
				// throw new Exception("Payment is already updated in JobProgress");
			}

			$customerReponse = QBCustomer::get($payment['CustomerRef']);

			if(!ine($customerReponse, 'entity')) {
				throw new Exception("Customer not found on QuickBooks");
			}

			//Author Anoop.
			// as we know that payment is already synched so we dont have to check these

			// $customer = QuickBooks::toArray($customerReponse['entity']);

			// $job = QBCustomer::getJob($customer);

			// if (!$job && $customer['Job'] == 'true') {
			// 	throw new JobNotSyncedException(['job_id' => $customer['Id']]);
			// }

			// $isMultiJob = QBCustomer::getProjects($customer['Id']);

			// $parentCustomerId = QBCustomer::getParentCustomerId($customer);

			// $jpCustomer = $this->customerRepo->getByQBId($parentCustomerId);

			// if (!$jpCustomer) {
			// 	throw new ParentCustomerNotSyncedException(['parent_customer_id' => $parentCustomerId]);
			// }

			// if (!$job) {
			// 	$job = QBCustomer::getJob($customer, $jpCustomer, $isMultiJob);
			// }

			// if (!$job) {
			// 	throw new Exception("Unable to find job in JobProgress.");
			// }

			$lines = QBPayment::getUnSyncedLines($payment);

			if (!empty($lines)) {
				throw new PaymentLineNotSyncedException($lines);
			}

			$job = $jpPayment->job;
			$jpPayment = $this->upatePaymentCommon($payment, $job);
			
			DB::commit();

			return $jpPayment;

		} catch (Exception $e) {

			DB::rollback();
			Log::error('Unable to Update Payment', [(string) $e]);
			throw $e;
		}
    }

    public function delete($qbId)
    {
        $jpPayment = QuickBooks::getJobPaymentByQBId($qbId);

		if(!$jpPayment) {
			echo "Payment not synced in JP";
			throw new Exception("Payment is not synced with JobProgress.");
		}

		try {

			DB::beginTransaction();

			$this->jobPaymentsRepo->jobPaymentCancel($jpPayment['id'], $jpPayment->job_id);

			DB::commit();

		} catch (Exception $e) {

			DB::rollback();
			Log::error('Unable to Update Delete', [(string) $e]);

			throw $e;
		}
    }

    public function get($qbId)
    {
        return QuickBooks::findById('payment', $qbId);
    }

	public function upatePaymentCommon($payment, $job)
	{
		$paymentMapped = $this->jobPaymentReverseMap($job, $payment);

		$payment = $this->jobPayment($paymentMapped, $payment);

		return $payment;
	}

	public function updateUnSyncedPayments($job, $excludeId = null)
	{
		/**
		 * This is due when we apply the unapplied payments to invoice on Quickbooks. Quickbooks do not
		 * fire webhook to hadle this created this
		 */
	}

    /**
	 * Payment Data Map
	 */
	public function jobPaymentReverseMap($job, $payment)
	{	
		$invoice = null;

		$serialNumber  = $this->financialPayment->getJobPaymentSerialNumber();

		$jpPayment = QuickBooks::getJobPaymentByQBId($payment['Id']);

		$mapInput = [];

		if($jpPayment) {			

			// In edit case just put id
			$mapInput['id'] = $jpPayment->id;

			// If payment amount it self is modified in QuickBooks by User
			if($jpPayment->payment != $payment['TotalAmt']) {
				$mapInput['amount_edited'] = true;
				$mapInput['orinal_amount'] = $jpPayment->payment;
			}
		}

		$mapInput = array_merge($mapInput, [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'serial_number' => $serialNumber,
			'status' => ($payment['UnappliedAmt'] > 0) ? JobPayment::UNAPPLIED : JobPayment::CLOSED,
			'payment' => $payment['TotalAmt'],
			'quickbook_id' => $payment['Id'],
			'quickbook_sync_token' => $payment['SyncToken'],
			'quickbook_sync' => true,
			'unapplied_amount' => $payment['UnappliedAmt'],
			'date' => $payment['TxnDate'],
			'echeque_number' => $payment['PaymentRefNum'],	
		]);

		$paymentMethodRef = $payment['PaymentMethodRef'];

		if(empty($paymentMethodRef)) {

			$jpPaymentMethod = $this->paymentMethodsRepo->getByLabel("Other");

			$mapInput['method'] = $jpPaymentMethod['method'];
			
		} else {

			$paymentMehtod = QBPaymentMethod::get($paymentMethodRef);

			if (!ine($paymentMehtod, 'entity')) {
				$jpPaymentMethod = $this->paymentMethodsRepo->getByLabel("Other");
				$mapInput['method'] = $jpPaymentMethod['method'];
				
			}else{

				$paymentMehtod = QuickBooks::toArray($paymentMehtod['entity']);
				
				$jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($paymentMehtod['Name']);
	
				if(!$jpPaymentMethod) {
					throw new PaymentMethodNotSyncedException(['id' => $paymentMehtod['Id']]);
				}

				$mapInput['method'] = $jpPaymentMethod['method'];
			}	

		}

		// If payment is linked with invoice or credit 

		if(isset($payment['Line'])) {

			$lines = $payment['Line'];

			if(isset($lines[0]) && is_array($lines[0])) {

				foreach($lines as $key => $line) {

					if($line['LinkedTxn']['TxnType'] == 'Invoice') {
 						
						$qbId = $line['LinkedTxn']['TxnId'];

						$invoice = QuickBooks::getJobInvoiceByQBId($qbId);

						if ($invoice) {

							$mapInput['lines'][] = [ 
								'type' => 'invoice',
								'jpId' => $invoice->id,
								'qbId' => $qbId,
								'amount' => $line['Amount'],
							];
						}
					}

					if($line['LinkedTxn']['TxnType'] == 'CreditMemo') {
						
						$qbId = $line['LinkedTxn']['TxnId'];
						
						$jobCredit = QuickBooks::getJobCreditByQBId($qbId);

						if($jobCredit) {

							$mapInput['lines'][] = [
								'type' => 'credit_memo',
								'jpId' => $jobCredit->id,
								'qbId' => $qbId,
								'amount' => $line['Amount'],
							];
						}
					}
				}

			} else {

				if($lines['LinkedTxn']['TxnType'] == 'Invoice') {
						
					$qbInvoiceId = $lines['LinkedTxn']['TxnId'];

					$invoice = QuickBooks::getJobInvoiceByQBId($qbInvoiceId);

					if($invoice) {

						$mapInput['lines'][] = [
							'type' => 'invoice',
							'jpId' => $invoice->id,
							'qbId' => $qbInvoiceId,
							'amount' => $lines['Amount'],
						];
					}
				}
			}
		}

		return $mapInput;
    }
    
    public function jobPayment($paymentData, $qbPayment) 
	{
		DB::beginTransaction();
		
		try {
			$jobIds = [];
			
			$payment = $this->financialPayment->updatePaymentwithFinancials($paymentData, $qbPayment);
			
			DB::commit();
			$jobIds[] = $payment->job_id;
			//update ref job financial
			$refJobIds = JobPayment::where('customer_id', $payment->customer_id)
				->where('ref_id', $payment->id)
				->whereNull('ref_to')
				->pluck('job_id')
				->toArray();
			if(!empty($refJobIds)){
				$jobIds = array_merge($jobIds, $refJobIds);
				$jobIds = arry_fu($jobIds);

			}
			foreach ($jobIds as $jobId) {
				JobFinancialCalculation::updateFinancials($jobId);
			}

			return $payment;
			
		} catch(Exception $e) {

			DB::rollback();	

			throw $e;
		}
	}


	/**
	 * Cancel Credit Payment
	 * @param  Instance $payment  Payment
	 * @return boolean
	 */
	
	public function cancelCreditPayment($payment)
	{
		try {

			if($payment->quickbook_id) {
				$param = [
					'query' => "SELECT *  FROM Payment WHERE Id = '".$payment->quickbook_id."'"
				];
				
				$queryResponse = QuickBooks::getDataByQuery($param['query']);

				if(empty($queryResponse)) {
					return false;
				}
				
				QuickBooks::getDataService()->Delete($queryResponse[0]);
			}
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * @param  Instance $payment        Payment
	 * @param  Int   	$jobQuickbookId Quickbook Id
	 * @return Mapped Payment Data
	 */

	public function paymentByBatchRequset($payment, $referenceId) {
		
		$data = $this->jobInvoicePaymentDataMap($payment, $referenceId);

		return $data;
	}


	/**
	 * Payment Data Map
	 * @param  Instance $payment Job Payment
	 * @param  Int      $jobQuickbookId Quickbook Id
	 * @return Payment Data
	 */
	public function jobInvoicePaymentDataMap($payment, $referenceId) 
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

		$mapInput = [
			'CustomerRef' => [
				'value' => $referenceId,
			],
			'TotalAmt' => $payment->payment,
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
			$mapInput['UnappliedAmt'] = $payment->unapplied_amount;
		}
		
		$invoiceLists = DB::select(DB::raw("select sum(invoice_payments.amount) as amount, quickbook_invoice_id, invoice_id, invoice_number from `invoice_payments` inner join (SELECT * FROM job_invoices) as job_invoices on `job_invoices`.`id` = `invoice_payments`.`invoice_id` where `payment_id` = " . $payment->id . " and `quickbook_invoice_id` is not null group by `job_invoices`.`id`"));

		if(!empty($invoiceLists)) {
			
			$count = 0;

			foreach ($invoiceLists as $invoice) {
				
				$quickbookInvoice = QBInvoice::getQuickbookInvoice($invoice->quickbook_invoice_id, $invoice->invoice_number);

				if (!$quickbookInvoice) {	
					continue;
				}

				$txnData[0] = [
					'TxnId'   => $quickbookInvoice->Id,
				    'TxnType' => "Invoice"
				];

				$LinkedTxn = [
					'LinkedTxn' => $txnData,
					'Amount' => number_format($invoice->amount, 2, '.', '')
				];

				$mapInput['Line'][$count++] = $LinkedTxn;
			}
		}

		$payentMethodRefId = $this->getPaymentReference(ucfirst($data['method']));

		if($payentMethodRefId) {
			$mapInput['PaymentMethodRef']['value'] = $payentMethodRefId;
		}

		if($payment->echeque_number) {
			$mapInput['PaymentRefNum'] =  $payment->echeque_number;
		}

		if($payment->date) {
			$mapInput['TxnDate'] = $payment->date;
		}

		return $mapInput;
	}

	/**
	 * Get Payment Reference Method id
	 * @param  String  $method Payment Method
	 * @return Payment Method Id
	 */
	public function getPaymentReference($method) 
	{

		try {

			$id = null;
		
			if($method === 'Cc') {
				$method = 'Credit Card';
			}
			
			$param = [
				'query' => "SELECT *  FROM PaymentMethod  WHERE name = '".addslashes($method)."'"
			];
			
			$queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(QuickBooks::isValidResponse($queryResponse, '\QuickBooksOnline\API\Data\IPPPaymentMethod')) {
				$id = $queryResponse[0]->Id;
			} else {
				$id = $this->createPaymentMethod($method);
			}
 
			return $id;

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}	
	}

	/**
	 * Create Payment Method
	 * @param  String $method Payment Method 
	 * @return Int Payment Method Id
	 */

	public function createPaymentMethod($method)
	{
		try {

			$paymentEntity = [
				'Name' => $method
			];

			$paymentMethod =  new \QuickBooksOnline\API\Data\IPPPaymentMethod($paymentEntity);

			$payment = QuickBooks::getDataService()->Add($paymentMethod);

			return $payment->Id;

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * @method create customer payment on quickbook and check they are exist on quickbook online
	 * @param  \Customer $customer [description]
	 * @return [void] [description]
	 */

	public function paymentsSync($paymentIds, $referenceId)
	{

		try {

			if(empty(array_filter((array)$paymentIds))) return true;

			if(!$referenceId) return false;
	
			goto batch;

			batch: {
				JobPayment::whereIn('id', (array)$paymentIds)
					->whereNull('ref_id')
					->whereNull('credit_id')
					->whereNull('canceled')->update([
					'quickbook_sync' => false			
				]);

				$query = JobPayment::whereIn('id', (array)$paymentIds)
					->whereNull('ref_id')
					->whereNull('credit_id')
					->whereNull('canceled');

				$query->chunk(30, function($payments) use ($referenceId) {

					foreach ($payments as $key => $payment) {
						
						// map payment data for batch request
						$data = $this->paymentByBatchRequset($payment, $referenceId);
						$paymentData[$key]['entity'] = 'Payment';
						$paymentData[$key]['data'] = \QuickBooksOnline\API\Facades\Payment::create($data);
						$paymentData[$key]['bId'] = $payment->id; 
						$paymentData[$key]['operation'] = 'create';
					}

					$batchData['BatchItemRequest'] = $paymentData;

					try {
						
						$response = QuickBooks::batchRequest($batchData);
						
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

								JobPayment::where('id', $payment->id)->update($paymentResponse);
							}
						}
						
					} catch(Exception $e) {
						throw new Exception($e->getMessage());
					}

				});
			}
			
			// $pendingCount = JobPayment::whereIn('id', (array)$paymentIds)
			// 	->whereQuickbookSync(false)
			// 	->whereNull('ref_id')
			// 	->whereNull('canceled')
			// 	->count();

			// if($pendingCount) {
			// 	$paymentIds = JobPayment::whereIn('id', (array)$paymentIds)
			// 		->whereNull('ref_id')
			// 		->whereQuickbookSync(false)
			// 		->whereNull('canceled')
			// 		->lists('id');

			// 	// goto batch;
			// }

			return true;

		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Invoice Payment
	 * @param  Instance $payment JobPayment
	 * @return Payment Instance
	 */
	public function invoicePayment($payment)
	{
		try {

			$job = $payment->job;
			
			$customer = $job->customer;
			
			$referenceId = QBCustomer::getCustomerQuickbookId($customer);
			
			$quickbookId = $payment->quickbook_id;
			
			if(!empty($quickbookId)) {
				
				$response = $this->get($quickbookId);

				$resultingPayment = $response['entity'];

				$payment->update([
					'quickbook_id'         => $resultingPayment->Id,
					'quickbook_sync_token' => $resultingPayment->SyncToken,
					'quickbook_sync'       => true
				]);
				
			} else {

				$paymentData = $this->jobInvoicePaymentDataMap($payment, $referenceId);

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

	/**
	 * Delete Payment from QuickBooks
	 * @param  Instance $payment  Payment
	 * @return boolean
	 */
	public function qbDeletePayment($payment)
	{
		try {
			
			if(empty($payment->quickbook_id)) {
				return false;
			}

			$param = [
				'query' => "SELECT *  FROM Payment WHERE Id = '".$payment->quickbook_id."'"
			];
			
			$queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(empty($queryResponse)) {
				return false;
			}

			QuickBooks::getDataService()->Delete($queryResponse[0]);
			$data = [
				'company_id' => getScopeId(),
				'customer_id' => $payment->customer_id,
				'job_id' => $payment->job_id,
				'qb_customer_id' => $queryResponse[0]->CustomerRef,
				'data' => json_encode($queryResponse),
				'object' => 'Payment',
				'created_by' => Auth::user()->id,
				'payment_id' => $payment->id,
				'qb_payment_id' => $queryResponse[0]->Id,
				'created_at' => Carbon::now()->toDateTimeString(),
				'updated_at' => Carbon::now()->toDateTimeString(),
			];

			DB::table('deleted_quickbook_payments')->insert($data);
			
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Create or Return JobProgress Payment
	 */

	public function getOrCreatePayment($qbId)
	{
		$payment = QuickBooks::getJobPaymentByQBId($qbId);

		if (!$payment) {
			
			$this->create($qbId);

			$payment = QuickBooks::getJobPaymentByQBId($qbId);


			if(!$payment) {
				throw new Exception("Unable to create Payment in JobProgress");
			}
		}

		return $payment;
	}

	public function isCustomerAccountSynced($id, $origin)
	{
		if($origin){
			$jobPayment = JobPayment::where('quickbook_id', $id)->first();
		}else{
			$jobPayment = JobPayment::whereId($id)->first();
		}

		if(!$jobPayment) return false;

		$customerId = $jobPayment->customer_id;

		$customer = Customer::where('company_id', getScopeId())->where('id', $customerId)->first();

		if(!$customer) return false;

		return (bool)$customer->quickbook_id;
	}

	public function getUnsyncedLines($payment)
	{
		$mapInput = [];

		if (isset($payment['Line'])) {

			$lines = $payment['Line'];

			if (isset($lines[0]) && is_array($lines[0])) {

				foreach ($lines as $key => $line) {

					if ($line['LinkedTxn']['TxnType'] == 'Invoice') {

						$qbId = $line['LinkedTxn']['TxnId'];

						$invoice = QuickBooks::getJobInvoiceByQBId($qbId);

						if (!$invoice) {

							$mapInput[] = [
								'type' => 'Invoice',
								'id' => $qbId,
							];
						}
					}

					if ($line['LinkedTxn']['TxnType'] == 'CreditMemo') {

						$qbId = $line['LinkedTxn']['TxnId'];

						$jobCredit = QuickBooks::getJobCreditByQBId($qbId);

						if (!$jobCredit) {

							$mapInput[] = [
								'type' => 'CreditMemo',
								'id' => $qbId
							];
						}
					}
				}
			} else {

				if ($lines['LinkedTxn']['TxnType'] == 'Invoice') {

					$qbInvoiceId = $lines['LinkedTxn']['TxnId'];

					$invoice = QuickBooks::getJobInvoiceByQBId($qbInvoiceId);

					if (!$invoice) {

						$mapInput[] = [
							'type' => 'Invoice',
							'id' => $qbInvoiceId,
						];
					}
				}
			}
		}

		return $mapInput;
	}
}