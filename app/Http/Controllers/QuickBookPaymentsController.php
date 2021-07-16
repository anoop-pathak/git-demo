<?php

namespace App\Http\Controllers;

use Request;
use App\Models\JobInvoice;
use Illuminate\Support\Facades\DB;
use App\Repositories\JobRepository;
use App\Exceptions\QuickBookException;
use Illuminate\Support\Facades\Redirect;
use App\Repositories\QuickBookRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\JobInvoiceRepository;
use App\Exceptions\QuickBookPayments\QuickBookOAuth2Exception;
use App\Services\QuickBookPayments\Service as QuickBookService;
use App\Services\FinancialDetails\FinancialPayment as PaymentService;
use App\Exceptions\QuickBookPayments\QuickBookPaymentsException as QBPayException;
use App\Services\QuickBookPayments\QuickBookServiceForSubscriber as QuickBookServiceForSubscriber;
use App\Services\QuickBooks\Facades\QuickBooks;
use Illuminate\Support\Facades\Queue;

class QuickBookPaymentsController extends Controller
{
	public function __construct(JobRepository $jobRepo, JobInvoiceRepository $jobInvoiceRepo, PaymentService $paymentService)
	{
		parent::__construct();
		$this->jobRepo = $jobRepo;
		$this->jobInvoiceRepo = $jobInvoiceRepo;
		$this->paymentService = $paymentService;
	}
 	public function makePayment()
	{
		try {
			$this->checkIfValidPaymentBeingMade(Request::input());
 			$validator = $this->validateCreditCard(Request::input('credit_card'));
			if($validator->fails()) {
				return Redirect::back()->withErrors($validator);
			}
 			$invoice_ids = Request::input('invoices');
			$amount = Request::input('amount');
			$creditCardDetails = Request::input('credit_card');
 			$this->findTheCompanyByInvoiceAndSetScope($invoice_ids);
			$this->service = new QuickBookService;
 			$jobs = $this->jobRepo->getJobsByInvoiceIds($invoice_ids);
 			$job = $jobs->first();
 			$invoices = $this->jobInvoiceRepo->getOpenInvoicesByIds($invoice_ids);
 			$response = $this->paymentService->paymentViaQuickbook($creditCardDetails, $amount, $invoices, $job);

 			if(Request::exists('redirect-web')) {
				return Redirect::to('customer_job_preview/'. $job->share_token)->with('payment-response', $response);
			}

 			return response()->view('quickbooks.payment-made', $response, 200);
			
		}
		
		catch(QuickbookException $e) {
			return response()->view('quickbooks.payment-made', [
				'status' => 0,
				'message' => $e->getMessage()
			], 500);
		}
 		catch (QBPayException $e) {
			return response()->view('quickbooks.payment-made', [
				'status' => 0,
				'message' => $e->getMessage()
			], 500);		
		}
		
		catch (\Exception $e) {
			return response()->view('quickbooks.payment-made', [
				'status' => 0,
				'message' => $e->getMessage()
			], 400);		
		}
 	}
 	public function checkQuickbooksConnected()
	{
		$subscriberService = new QuickBookServiceForSubscriber;
		if($subscriberService->checkCurrentCompanyQuickbooksConnected()) {
			$response = [
				'status' => 1,
				'message' => 'Company is Connected',
			];
		} else {
			$response = [
				'status' => 0,
				'message' => 'Company is not Connected',
			];
		}
 		return $response;
	}
 	public function authoriseQuickbook($redirect = TRUE)
	{
		$subscriberService = new QuickBookServiceForSubscriber;
		$url = $subscriberService->getAuthorisationUrl();
 		if($redirect) {
			return redirect($url);
		} else {
			return $url;
		}
	}
 	public function oauth2Callback()
	{
		try {
 			$url = (Request::getPathInfo() . (Request::getQueryString() ? ('?' . Request::getQueryString()) : ''));
			$state = Request::input('state');
			
			$state = json_decode($state);
			// setScopeId($state->company);
			setAuthAndScope($state->current_login_user_id);
 			$this->service = new QuickBookServiceForSubscriber;
			$this->service->setState($state);
 			$authTokenObject = $this->service->getAuthTokenFromCallback($url);
			
			DB::beginTransaction();
			
			$accessTokenObject = $this->service->createAccessToken($authTokenObject);
			
			DB::commit();
 			# This is just a double check for the access token whether got and saved or not
			$this->service->checkCurrentCompanyQuickbooksConnected(1);

			$data = [
				'company_id' => getScopeId(),
				'customer_import_by' => Auth::id(),
				'current_login_user_id' => Auth::id(),
			];
			Queue::connection('qbo')->push('\App\Services\QuickBooks\QueueHandler\QB\ItemHandler@defaultItemsCreateTask', $data);

			Queue::connection('qbo')->push('\App\Services\QuickBooks\QueueHandler\QB\CustomerHandler@import', $data);

 			return view('quickbooks.oauth2connected', [
				'connected' => true,
				'message' => 'Connected with Quickbooks Successfully'
			]);
		} 
		
		catch (\Exception $e) {
			
			DB::rollback();
			return view('quickbooks.oauth2connected', [
				'connected' => false,
				'message' => $e->getMessage()
			], 500);
		}
 		catch(QuickBookOAuth2Exception $e) {
			return view('quickbooks.oauth2connected', [
				'connected' => false,
				'message	' => $e->getMessage()
			]);
		}
 	}
 	private function findTheCompanyByInvoiceAndSetScope($invoices)
	{
		$job = JobInvoice::whereIn('id', $invoices)->groupBy('job_id')->get(['job_id'])->first();
		QuickBooks::setCompanyScope(null, $job->job->company->id);
	}
 	private function validateCreditCard($credit_card)
	{
		$validator = Validator::make($credit_card, [
			'name' => 'required',
			'cvc' => 'required|min:3|max:4',
			'number' => 'required|min:15|max:16',
			'expYear' => 'required|numeric|min:' . date('Y') . '|max:2040',
			'expMonth' => 'required|numeric|min:1|max:12',
		]);
 		return $validator;
	}
 	private function checkIfValidPaymentBeingMade($requestData)
	{
 		$invoices = $requestData['invoices'];
		$amount = $requestData['amount'];
		
		# Check invoices provided are the right ones i.e checking if the invoice id being passed belong to the same job, this is enough validation
		$jobs = JobInvoice::whereIn('id', $invoices);
		$jobs_count = clone $jobs;
 		$jobs_count = $jobs_count->groupBy('job_id')->get(['job_id'])->count();
		
		if($jobs_count > 1) {
			throw new \Exception("Something Went Wrong! Try Again!");
		}
 		# Check the payment is equal to total of the invoices amount
		# Here invoice amount - payment recieved must be available
		$invoiceAmount = 0;
		$invoices = clone $jobs;
		$invoices  = $invoices->get();
		foreach ($invoices as $invoice) {
			$invoiceAmount += $invoice->open_balance;
		}
 		$amount = (double)$amount;
		$invoiceAmount = (double)$invoiceAmount;
 		# double cant be compared; so this logic
		if(!(abs($invoiceAmount - $amount) < 0.0001)) {
			throw new \Exception("Amount being Paid doesn't match with the Invoices Amount");
		}
 		return TRUE;
	}
}