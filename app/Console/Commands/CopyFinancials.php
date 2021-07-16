<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\FinancialDetails\FinancialPayment;
use App\Services\JobInvoices\JobInvoiceService;
use App\Repositories\ChangeOrdersRepository;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobFinancialCalculation;
use App\Models\InvoicePayment;
use App\Models\JobPayment;
use App\Models\JobInvoice;
use App\Models\ChangeOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App;
use Exception;

class CopyFinancials extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:copy_financials';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "This Command is used to Import already Copied Job's Financials. 'customers_and_jobs_copy_ref' table is used to get ids of Copied Jobs";

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
	public function fire()
	{
		$copyFromCompanyId = $this->ask("Please enter company id from which you want to copy the records: ");
		$fromCompany = Company::findOrFail($copyFromCompanyId);

		$this->copyToCompanyId = $this->ask("\nPlease enter company id in which you want to copy the records: ");
		$company = Company::findOrFail($this->copyToCompanyId);

		$refData = DB::table('customers_and_jobs_copy_ref')
			->where('from_company_id', $copyFromCompanyId)
			->where('ref_type', 'Job')
			->get();
		$this->financialPayment = App::make(FinancialPayment::class);
		$this->jobInvoiceService = App::make(JobInvoiceService::class);
		$this->changeOrderRepo = App::make(ChangeOrdersRepository::class);

		if(!$refData){
			$this->info('There is no Data to Copy. First import Customers/Jobs');
			return false;
		}

		$total = count($refData);

		$systemUser = $company->anonymous;
		setScopeId($company->id);

		Auth::login($systemUser);

		$startedAt = $currentDateTIme = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: $startedAt -----");
		$this->info("----- Total Data:  $total");

		DB::beginTransaction();
		try{
			foreach ($refData as $data) {
				$job = Job::where('company_id', $copyFromCompanyId)
					->where('id', $data->from_ref_id)
					->first();
				$toJob = Job::where('company_id', $company->id)
					->where('id', $data->to_ref_id)
					->first();
				if(!$job || !$toJob) continue;

				$this->copyJobPayments($data, $job, $toJob);
				$this->info("----- Job Payments Copied. -----");
				
				$this->copyJobInvoices($data, $job, $toJob);
				$this->info("----- Job Invoices Copied. -----");
				
				$this->copyInvoicePayments($data, $job, $toJob);
				$this->info("----- Invoice Payments Copied. -----");
				
				JobFinancialCalculation::updateFinancials($toJob->id);

				$total--;
				$this->info("Pending data: $total");

			}

		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		DB::commit();

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Command completed at: $completedAt -----");
	}


	private function copyJobPayments($refData, $fromJob, $toJob)
	{
		$invoicePaymentsIds = InvoicePayment::where('job_id', $fromJob->id)->pluck('payment_id')->toArray();

		$jobPayments = JobPayment::where('job_id', $fromJob->id)
			->whereNull('credit_id')
			->whereNull('canceled')
			->whereNull('ref_id')
			->whereNotIn('id', (array)$invoicePaymentsIds)
			->get();
		if(!$jobPayments) return;

		foreach ($jobPayments as $payment) {
			$this->createPayment($payment, $fromJob, $toJob);
		}
	}

	private function copyJobInvoices($data, $fromJob, $toJob)
	{
		$invoiceIds = InvoicePayment::where('job_id', $fromJob->id)->pluck('invoice_id')->toArray();

		$jobInvoices = JobInvoice::where('job_id', $fromJob->id)
			->whereNotIn('id', (array)$invoiceIds)
			->get();

		foreach ($jobInvoices as $jobInvoice) {

			$this->createInvoice($jobInvoice, $fromJob, $toJob);
		}
	}


	private function copyInvoicePayments($data, $fromJob, $toJob)
	{
		$invoicePayments = InvoicePayment::where('job_id', $fromJob->id)->get();

		foreach ($invoicePayments as $invoicePayment) {

			$jobPayment = JobPayment::where('job_id', $fromJob->id)
				->where('id', $invoicePayment->payment_id)
				->whereNull('credit_id')
				->whereNull('canceled')
				->whereNull('ref_id')
				->first();
			$newPayment = $this->createPayment($jobPayment, $fromJob, $toJob);

			if(!$newPayment) continue;

			$jobInvoice = JobInvoice::where('job_id', $fromJob->id)
				->where('id', $invoicePayment->invoice_id)
				->first();
			$newJobInvoice = $this->createInvoice($jobInvoice, $fromJob, $toJob);
			if(!$newJobInvoice) continue;

			$invoicePaymentObj = new InvoicePayment;
			$invoicePaymentObj->invoice_id = $newJobInvoice->id;
			$invoicePaymentObj->job_id = $toJob->id;
			$invoicePaymentObj->payment_id = $newPayment->id;
			$invoicePaymentObj->amount = $invoicePayment->amount;
			$invoicePaymentObj->save();

			$newJobInvoice = JobInvoice::find($newJobInvoice->id);

			$this->jobInvoiceService->updatePdf($newJobInvoice);
		}
	}

	private function getInvoiceLines($jobInvoice)
	{
		$linesData = [];
		$lines = $jobInvoice->lines;
		foreach ($lines as $line) {
			$data['amount']=	$line->amount;
			$data['quantity']=	$line->quantity;
			$data['description']= $line->description;
			$data['is_chargeable']=	$line->is_chargeable;
			//for this Need to copy product as well
			// if($line->product_id){
			// 	$data['product_id']  = $line->product_id;
			// }
			$linesData[] = $data;
		}

		return $linesData;
	}

	private function createPayment($payment, $fromJob, $toJob)
	{
		$isExists = DB::table('customers_and_jobs_copy_ref')
			->where('ref_type', 'Payment')
			->where('from_ref_id', $payment->id)
			->where('from_company_id', $fromJob->company_id)
			->where('to_company_id', $toJob->company_id)
			->first();
		if($isExists) return false;
		$jobPayment = new JobPayment;
		$jobPayment->job_id         = $toJob->id;
		$jobPayment->payment        = $payment->payment;
		$jobPayment->unapplied_amount = $payment->unapplied_amount;
		$jobPayment->customer_id    = $toJob->customer_id;
		$jobPayment->method         = $payment->method;
		$jobPayment->echeque_number = $payment->echeque_number;
		$jobPayment->date           = $payment->date;
		$jobPayment->status         = $payment->status;
		$jobPayment->quickbook_sync = false;
		$jobPayment->serial_number  = $this->financialPayment->getJobPaymentSerialNumber();
		$jobPayment->ref_id = null;
		$jobPayment->modified_by = Auth::id();
		$jobPayment->save();

		DB::table('customers_and_jobs_copy_ref')->insert([
			'ref_type' => 'Payment',
			'from_company_id' => $fromJob->company_id,
			'from_ref_id' => $payment->id,
			'to_company_id' => $toJob->company_id,
			'to_ref_id' => $jobPayment->id,
			'created_at' => Carbon::now()->toDateTimeString(),
			'updated_at' => Carbon::now()->toDateTimeString()
		]);

		return $jobPayment;

	}

	private function createInvoice($jobInvoice, $fromJob, $toJob)
	{
		$isExists = DB::table('customers_and_jobs_copy_ref')
			->where('ref_type', 'Invoice')
			->where('from_ref_id', $jobInvoice->id)
			->where('from_company_id', $fromJob->company_id)
			->where('to_company_id', $toJob->company_id)
			->first();
		if($isExists) return false;

		if($jobInvoice->changeOrder){
			$invoice = $this->createChangeOrderInvoice($jobInvoice, $fromJob, $toJob);
			return $invoice;
		}

		$serialNumber = $this->jobInvoiceService->getInvoiceNumber();
		$data = [];
		$data['job_id'] = $toJob->id;
		$data['name'] = $jobInvoice->name;
		$data['date'] = $jobInvoice->date;
		$data['note'] = $jobInvoice->note;
		$data['invoice_number'] = $serialNumber;
		$data['division_id'] = null;
		$data['update_job_price'] = true;
		$data['taxable'] = false;


		$linesData = $this->getInvoiceLines($jobInvoice);
		$data['lines'] = $linesData;
		$invoice = $this->jobInvoiceService->createJobInvoice($toJob, $linesData, $data);

		DB::table('customers_and_jobs_copy_ref')->insert([
			'ref_type' => 'Invoice',
			'from_company_id' => $fromJob->company_id,
			'from_ref_id' => $jobInvoice->id,
			'to_company_id' => $toJob->company_id,
			'to_ref_id' => $invoice->id,
			'created_at' => Carbon::now()->toDateTimeString(),
			'updated_at' => Carbon::now()->toDateTimeString()
		]);

		return $invoice;
	}

	private function createChangeOrderInvoice($jobInvoice, $fromJob, $toJob)
	{
		$oldChangeOrder = ChangeOrder::where('invoice_id', $jobInvoice->id)->first();

		if(!$oldChangeOrder){
			return false;
		}

		$data = [];
		$data['created_by'] = Auth::id();
		$data['order'] = ChangeOrder::where('job_id', $toJob->id)->count()+1;
		$data['job_id'] = $toJob->id;
		$data['name'] = $oldChangeOrder->name;
		$data['date'] = $oldChangeOrder->date;
		$data['create_without_invoice'] = false;
		$data['approved'] = false;
		$data['division_id'] = null;
		$data['taxable'] = false;

		$entitiesData = $this->getChangeOrderEntities($oldChangeOrder);
		$data['entities'] = $entitiesData;
		$changeOrder = $this->changeOrderRepo->save($toJob, $data['entities'], $data);

		//create invoice
		$invoice = $this->jobInvoiceService->updateChangeOrderInvoice($changeOrder, $data);

		$changeOrder->update([
			'invoice_id'      => $invoice->id,
			'invoice_updated' => true
		]);

		DB::table('customers_and_jobs_copy_ref')->insert([
			'ref_type' => 'Invoice',
			'from_company_id' => $fromJob->company_id,
			'from_ref_id' => $jobInvoice->id,
			'to_company_id' => $toJob->company_id,
			'to_ref_id' => $invoice->id,
			'created_at' => Carbon::now()->toDateTimeString(),
			'updated_at' => Carbon::now()->toDateTimeString()
		]);

		return $invoice;

	}

	private function getChangeOrderEntities($changeOrder)
	{
		$entitiesData = [];
		$entities = $changeOrder->entities;
		foreach ($entities as $entity) {
			$data['amount']=	$entity->amount;
			$data['quantity']=	$entity->quantity;
			$data['description']= $entity->description;
			$data['is_chargeable']=	$entity->is_chargeable;
			//for this Need to copy product as well
			// if($entity->product_id){
			// 	$data['product_id']  = $entity->product_id;
			// }
			$entitiesData[] = $data;
		}

		return $entitiesData;
	}
}
