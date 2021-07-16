<?php
namespace App\Services\Credits;

use Auth;
use App\Models\JobPayment;
use App\Models\Customer;
use App\Models\Job;
use App\Services\QuickBooks\QuickBookService;
use App\Exceptions\QuickBookException;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\JobsTransformer;
use App\Transformers\JobInvoiceTransformer;
use App\Models\InvoicePayment;
use App\Exceptions\NoCreditAvailableException;
use App\Exceptions\CreditLessThanInvoicePaymentException;
use App\Models\JobInvoice;
use App\Transformers\JobPaymentTransformer;
use App\Models\JobPaymentNumber;
use QBDesktopQueue;
use App\Services\JobInvoices\JobInvoiceService as JobInvoiceService;
use App\Repositories\JobCreditRepository;
use App\Services\FinancialDetails\FinancialPayment;
use Carbon\Carbon;
use App\Models\JobCredit;
use App\Models\JobFinancialCalculation;
use PDF;
use Exception;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use App\Events\CreditCreated;
use App\Events\CreditApplied;
use App\Events\CreditCancelled;
use App\Events\JobPaymentCancelled;
use App\Events\CreditPaymentCreated;
use Settings;
use FlySystem;

class JobCredits
{
	public function __construct(QuickBookService $quickBookService, Larasponse $response, JobInvoiceService $invoiceService, JobCreditRepository $repo, FinancialPayment $financialPayment)
	{
		$this->quickBookService = $quickBookService;
		$this->invoiceService 	= $invoiceService;
		$this->response 		= $response;
		$this->repo 			= $repo;
		$this->financialPayment = $financialPayment;
 	}
 	public function addCredits($data)
	{
		if(!$data) return [];
 		$token = QuickBooks::getToken();
		$job = Job::find($data['job_id']);
		$data['method'] = JobCredit::METHOD;
 		$data['status'] = JobCredit::UNAPPLIED;
		$data['unapplied_amount'] = $data['amount'];

		$jobCredit = $this->repo->create($data);
 		# create description for pdf #
		$description  = $this->getJobTradeDescription($job);
		# create pdf #
		$this->createCreditNotePdf($description, $jobCredit);
		$task = null;

 		if(!ine($data,'date')){
			$data['date'] = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
		}
		 // QBDesktopQueue::addCreditMemo($jobCredit->id);
		 if(!ine($data, 'invoice_id')) {

			JobFinancialCalculation::updateFinancials($jobCredit->job_id);
			Event::fire('JobProgress.Credits.Events.CreditCreated', new CreditCreated($jobCredit));
			return $jobCredit;
		 }
		$data['credit_details'][0]['credit_id'] = $jobCredit->id;
		$data['credit_details'][0]['amount'] = $data['amount'];
		$this->applyCredits($data);
 		$jobCredit = JobCredit::find($jobCredit->id);
 		return $jobCredit;
	}
 	public function applyCredits($data){

 		if(ine($data, 'credit_details')){
			$jobCredits = JobCredit::whereIn('id', array_column($data['credit_details'], 'credit_id'))
				->whereCompanyId(getScopeId())
				->get();
		}
		$jobId = null;

		$syncAccount = false;
		$applyCreditsIds = [];

		if(ine($data,'invoice_id')){
			$invoice = JobInvoice::findOrFail($data['invoice_id']);
		}
		foreach ($jobCredits as $jobCredit) {
			$jobId = $jobCredit->job_id;
 			$creditDetailsKey = arrayCSByValue($data['credit_details'], $jobCredit->id, 'credit_id');

			 if($creditDetailsKey === false) continue;

			$data['amount']    = $data['credit_details'][$creditDetailsKey]['amount'];
			$data['credit_id'] = $jobCredit->id;

			# code...
			if(!ine($data, 'method')){
				$data['method'] = JobCredit::METHOD;
			}

 			// if(ine($data,'invoice_id')){
			// 	$invoice = JobInvoice::findOrFail($data['invoice_id']);
			// }

 			if(!ine($data,'date')){
				$data['date'] = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();;
			}

			$job = $invoice->job;

 			# Following are the process to link credit to invoice #
			// $invoice = JobInvoice::findOrFail($data['invoice_id']);
			if(!ine($data, 'amount')){
				$data['amount'] = $jobCredit->unapllied_amount;
			}
 			$creditBalance = $jobCredit->unapplied_amount - $data['amount'];
			if($creditBalance > 0){
				$jobCredit->unapplied_amount = $creditBalance;
				$jobCredit->status = JobCredit::UNAPPLIED;
				$jobCredit->save();
			}
			if($creditBalance <= 0){
				$jobCredit->unapplied_amount = 0;
				$jobCredit->status = JobCredit::CLOSED;
				$jobCredit->save();
			}

			// if credit is applied to cross job.
			if($jobCredit->job_id != $job->id) {

				//add reffred from payment
				$joCreditRefId = new JobCredit;
				$joCreditRefId->company_id     = getScopeId();
				$joCreditRefId->customer_id    = $job->customer_id;
				$joCreditRefId->job_id         = $job->id;
				$joCreditRefId->amount        = $data['amount'];
				$joCreditRefId->unapplied_amount = 0;
				$joCreditRefId->method        	 = $data['method'];
				$joCreditRefId->date           = $data['date'];
				$joCreditRefId->status         = JobCredit::CLOSED;
				$joCreditRefId->quickbook_sync = false;
				$joCreditRefId->ref_id = $jobCredit->id;
				$joCreditRefId->save();

				$refId = $joCreditRefId->id;

				//add reffered to payment
				$jobCreditRefTo = new JobCredit;
				$jobCreditRefTo->job_id         = $jobCredit->job_id;
				$jobCreditRefTo->amount        = $data['amount'];
				$jobCreditRefTo->customer_id    = $job->customer_id;
				$jobCreditRefTo->method        	 = $data['method'];
				$jobCreditRefTo->date          	 = $data['date'];
				$jobCreditRefTo->status         = JobCredit::CLOSED;
				$jobCreditRefTo->quickbook_sync = false;
				$jobCreditRefTo->ref_id = $jobCredit->id;
				$jobCreditRefTo->ref_to = $refId;
				$jobCreditRefTo->save();
			}

			$amount = 0;
 			# Incase if same credit is apply on same invoice multiple times #
			// $jobPaymentIds = JobPayment::Where('credit_id',$jobCredit->id)->lists('id');
			$invoicePayment = InvoicePayment::whereCreditId($jobCredit->id)->whereInvoiceId($data['invoice_id'])->first();
 			$creditJobPayment = null;
			if($invoicePayment){
				$creditJobPayment = $invoicePayment->jobPayment;
				$data['amount'] = $invoicePayment->amount + $data['amount'];
				$invoicePayment->amount = $data['amount'];
				$invoicePayment->save();
				$creditJobPayment->payment = $data['amount'];
				$creditJobPayment->save();
			}
 			if(!$creditJobPayment){
				$creditJobPayment = $this->financialPayment->saveCreditJobPayment(
					$jobCredit->job_id,
					$jobCredit->id,
					$data['amount'],
					$jobCredit->customer_id,
					$data
				);
			}
 			if(!$invoicePayment) {
				$this->saveInvoicePayment(
					$creditJobPayment->job_id,
					$jobCredit->id,
					$creditJobPayment->id,
					$data['invoice_id'],
					$data['amount']
				);
			}

			$this->invoiceService->updatePdf($invoice);
			$isQBD = false;

			$userName = QBDesktopQueue::getUsername($jobCredit->company_id);

			if ($userName) {
				$isQBD = QBDesktopQueue::isAccountConnected($userName);
			}

			logx('asd');

 			if($creditJobPayment) {
				logx("job credits payment created.");
				Event::fire('JobProgress.FinancialDetails.Events.CreditPaymentCreated', new CreditPaymentCreated($creditJobPayment));
			}

			if(!$isQBD) {
				if (!$jobCredit->quickbook_id || !$invoice->quickbook_invoice_id) {

					$syncAccount = true;
				} else {
					Event::fire('JobProgress.Credits.Events.CreditApplied', new CreditApplied($jobCredit));
				}
			}
		}

		# create Credit Memo On Quickbook Online If Not created #
		if($syncAccount) {
			$jobCredit = JobCredit::findorFail($data['credit_details'][0]['credit_id']);
			Event::fire('JobProgress.Credits.Events.CreditCreated', new CreditCreated($jobCredit));
		}

 		if($jobId) {
			JobFinancialCalculation::updateFinancials($jobId);
		}

		return $jobCredit;
 	}
 	/**
	 * cancel job credit.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function cancelJobCredit($jobCredit)
	{
 		$jobCredit->update([
			'canceled' => Carbon::now()->toDateTimeString()
		]);
 		$jobPayments = $jobCredit->jobPayment;
 		$invoiceIds = [];
		foreach ($jobPayments as $jobPayment) {
			$invoicePayment = $jobPayment->invoicePayments();
			$invoiceId = $invoicePayment->pluck('invoice_id')->toArray();
			$invoicePayment->delete();
			$jobPayment->delete();
			$invoiceIds = array_merge($invoiceIds,$invoiceId);

			$extraParams['qb_desktop_txn_id'] = $jobPayment->qb_desktop_txn_id;
			// QBDesktopQueue::deletePayment($jobPayment->id, $user = null, $extraParams);
			Event::fire('JobProgress.FinancialDetails.Events.PaymentCancelled', new JobPaymentCancelled($jobPayment, $extraParams));
		}

		# update invoice pdf #
		$jobInvoices = JobInvoice::whereIn('id', arry_fu($invoiceIds))->get();
		foreach ($jobInvoices as $invoice) {
			$this->invoiceService->updatePdf($invoice);
		}
 		if($jobCredit->quickbook_id) {
			$token = QuickBooks::getToken();
			// $this->quickBookService->deleteCreditNote($token, $jobCredit);
			// QBCreditMemo::deleteCreditNote($jobCredit);

			if($token) {

				QBOQueue::addTask(QuickBookTask::QUICKBOOKS_CREDIT_DELETE, [
					'id' => $jobCredit->id,
				], [
					'object_id' => $jobCredit->id,
					'object' => QuickBookTask::CREDIT_MEMO,
					'action' => QuickBookTask::DELETE,
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			}
		}
		 // QBDesktopQueue::deleteCreditMemo($jobCredit->id);
		Event::fire('JobProgress.Credits.Events.CreditCancelled', new CreditCancelled($jobCredit));
		JobFinancialCalculation::updateFinancials($jobCredit->job_id);
 		return true;
	}
 	/**
	 * sync job credit
	 */
	public function syncJobUpdateCredits($token, $job, $creditIds = [])
	{
		$jobId = $job->id;
		$jobQuickbookId = QBCustomer::getJobQuickbookId($job);
		$jobCredits = JobCredit::whereIn('id', (array)$creditIds)
			->whereNull('canceled')
			->whereCompanyId(getScopeId())
			->whereJobId($jobId)
			->get();
		$deletedJobCredits = JobCredit::whereNotNull('canceled')
			->whereCompanyId(getScopeId())
			->whereJobId($jobId)
			->where('quickbook_id', '!=', '')
			->get();
 		$invoicePaymentsIds = InvoicePayment::whereJobId($jobId)
								->whereNotNull('credit_id')
								->pluck('payment_id')->toArray();
		$creditPaymentsIds = [];
		foreach ($jobCredits as $jobCredit) {
			# create description for pdf #
			$description  = $this->getJobTradeDescription( $jobCredit->job);
			# create pdf #
			$this->createCreditNotePdf($description, $jobCredit);
			# create Credit Memo On Quickbook Online If Not created #

			if(!$jobCredit->quickbook_id && $token) {
				# sync on Quickbooks #
				QBCreditMemo::createCreditNote($jobCredit, $description);
			}

			# get deleted invoice credit payment #
			$paymentsIds =  JobPayment::whereCreditId($jobCredit->id)
				->whereNotIn('id', $invoicePaymentsIds)
				->pluck('id')->toArray();
			$creditPaymentsIds = array_merge($creditPaymentsIds,$paymentsIds);
		}
 		$this->cancelJobCreditPayment($token, $creditPaymentsIds);
 		# delete credit on QBO which are deleted on jp #
		foreach ($deletedJobCredits as $deletedCredit) {
			QBCreditMemo::deleteCreditNote($deletedCredit);
		}
 		# sync credit payments #
		$response = QBCreditMemo::syncCredits($creditIds, $jobQuickbookId);
 		return true;
	}
 	/**
	 * sync job credit
	 */
	public function syncCredits($creditIds, $jobId)
	{
		$token = QuickBooks::getToken();
		$job = Job::find($jobId);
		// $jobQuickbookId = $this->quickBookService->getJobQuickbookId($token, $job);
		$jobQuickbookId = QBCustomer::getJobQuickbookId($job);

		$jobCredits = JobCredit::whereIn('id', (array)$creditIds)
			->whereNull('canceled')
			->whereCompanyId(getScopeId())
			->whereJobId($jobId)
			->get();
		$deletedJobCredits = JobCredit::whereNotNull('canceled')
			->whereCompanyId(getScopeId())
			->whereJobId($jobId)
			->where('quickbook_id', '!=', '')
			->get();
		$invoicePaymentsIds = InvoicePayment::whereJobId($jobId)
								->whereNotNull('credit_id')
								->pluck('payment_id')->toArray();
		$creditPaymentsIds = [];
		foreach ($jobCredits as $jobCredit) {
			# create description for pdf #
			$description  = $this->getJobTradeDescription($jobCredit->job);
			# create pdf #
			$this->createCreditNotePdf($description, $jobCredit);
			# create Credit Memo On Quickbook Online If Not created #
			if(!$jobCredit->quickbook_id && $token) {
				# sync on Quickbooks #
				QBCreditMemo::createCreditNote($jobCredit, $description);
			}
			# get deleted invoice credit payment #
			$paymentsIds =  JobPayment::whereCreditId($jobCredit->id)
				->whereNotIn('id', $invoicePaymentsIds)
				->pluck('id')->toArray();
			$creditPaymentsIds = array_merge($creditPaymentsIds,$paymentsIds);
 		}
 		$this->cancelJobCreditPayment($token, $creditPaymentsIds);
 		# delete credit on QBO which are deleted on jp #
		foreach ($deletedJobCredits as $deletedCredit) {
			QBCreditMemo::deleteCreditNote($deletedCredit);
		}
 		# sync credit payments #
		QBCreditMemo::syncCredits($creditIds, $jobQuickbookId);
 		JobFinancialCalculation::updateFinancials($jobId);
 		return true;
	}
 	public function cancelJobCreditPayment($token, $paymentsIds)
	{
		$jobPayments = JobPayment::whereIn('id', arry_fu($paymentsIds))->get();
		foreach ($jobPayments as $jobPayment) {
			QBPayment::cancelCreditPayment($jobPayment);
			$jobPayment->delete();
 		}
		return true;
 	}
 	/************************ PRIVATE METHOD *******************/
 	private function saveInvoicePayment($jobId, $creditId, $paymentId, $invoiceId, $amount)
	{
		InvoicePayment::create([
			'invoice_id' => $invoiceId,
			'credit_id'  => $creditId,
			'payment_id' => $paymentId,
			'amount'     => $amount,
			'job_id' 	 => $jobId
		]);
	}
 	private function createCreditNotePdf($description, $jobCredit)
	{
		$company  = $jobCredit->company;
		$fileName =  $jobCredit->id. Carbon::now()->timestamp .'.pdf';
		$basePath = 'job_credits/' . $fileName;
		$fullPath = config('jp.BASE_PATH').$basePath;
 		$pdf = PDF::loadView('jobs.job-credit-note', [
				'jobCredit'   => $jobCredit,
				'customer'    => $jobCredit->customer,
				'company'     => $company,
				'description' => $description
		  ])->setOption('page-size','A4')
			->setOption('margin-left', 0)
			->setOption('margin-right', 0)
			->setOption('margin-top', '0.8cm')
			->setOption('margin-bottom', '0.8cm');
 		FlySystem::put($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);
		$jobCredit->update([
			'file_path' => $basePath,
			'file_size' =>  FlySystem::getSize($fullPath)
		]);
 		return true;
	}

 	private function getJobTradeDescription($job)
	{
		$trades = $job->trades->pluck('name')->toArray();
		$description = $job->number;
 		// Append Other trade type decription if 'Other' trade is associated..
		if(in_array( 'OTHER', $trades) && ($job->other_trade_type_description)) {
			$otherKey = array_search('OTHER', $trades);
			unset($trades[$otherKey]);
			$other  = 'OTHER - ' . $job->other_trade_type_description;
			array_push($trades, $other);
		}
 		if($trade = implode(', ', $trades)) {
			$description .= ' / '.$trade;
		}
 		return $description;
	 }

	 /**
	 * Method used in Two way sync to add credit
	 */

	public function saveCredit($data, Job $job)
	{

		if(!$data) return [];

		$data['method'] = JobCredit::METHOD;

		$data['status'] = JobCredit::UNAPPLIED;

		$data['origin'] = QuickBookTask::ORIGIN_QB;

		$existingCredit = null;

		if(ine($data, 'quickbook_id')) {
			$existingCredit = Quickbooks::getJobCreditByQBId($data['quickbook_id']);
		}

		if(!empty($existingCredit)) {
			throw new Exception('Job Credit already exists');
		}

		$jobCredit = $this->repo->create($data);

		# create description for pdf #
		$description  = $this->getJobTradeDescription($job);
		# create pdf #
		$this->createCreditNotePdf($description, $jobCredit);

		# create Credit Memo On Quickbook Online If Not created #

		if(!ine($data,'date')) {
			$data['date'] = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
		}

		// JobFinancialCalculation::updateFinancials($jobCredit->job_id);

		return $jobCredit;
	}

	/**
	 * Method used in Two way sync to add credit
	 */

	public function updateJobCredit($data, Job $job)
	{

		if(!$data) return [];

		$jobCreditId = $data['id'];

		if(!ine($data,'date')) {
			$data['date'] = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
		}
		unset($data['id']);
		// Description for pdf #
		$description  = $this->getJobTradeDescription($job);

		DB::table('job_credits')
			->where('id', $jobCreditId)
				->update($data);

		$jobCredit = JobCredit::find($jobCreditId);

		# update pdf #
		$this->createCreditNotePdf($description, $jobCredit);

		// JobFinancialCalculation::updateFinancials($jobCredit->job_id);

		return $jobCredit;
	}


	/**
	 * cancel job credit by QBO.
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */

	public function qboCancelJobCredit($jobCredit)
	{

		$jobCredit->update([
			'canceled' => Carbon::now()->toDateTimeString()
		]);

		$jobPayments = $jobCredit->jobPayment;

		$invoiceIds = [];

		foreach ($jobPayments as $jobPayment) {

			$invoicePayment = $jobPayment->invoicePayments();
			$invoiceId = $invoicePayment->pluck('invoice_id')->toArray();
			$invoicePayment->delete();
			$jobPayment->delete();
			$invoiceIds = array_merge($invoiceIds,$invoiceId);
		}

		$jobInvoices = JobInvoice::whereIn('id', arry_fu($invoiceIds))->get();

		foreach ($jobInvoices as $invoice) {
			$this->invoiceService->updatePdf($invoice);
		}

		if($jobCredit->quickbook_id) {
			$token =QuickBooks::getToken();
			// $this->quickBookService->deleteCreditNote($token, $jobCredit);
			QBCreditMemo::deleteCreditNote($jobCredit);
		}

		JobFinancialCalculation::updateFinancials($jobCredit->job_id);

		return true;
	}
}