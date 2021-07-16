<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\QuickBooks\CustomerAccountHandlerTrait;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DeletedInvoicePayment;
use App\Models\Job;
use App\Models\JobFinancialCalculation;
use App\Models\QuickBookTask;
use App\Repositories\JobInvoiceRepository;
use Exception;
use App;
use Illuminate\Support\Facades\DB;

class RetoreCustomerFinancials extends Command {
	use CustomerAccountHandlerTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:restore_customer_financials';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command is used to restore customer Deleted(By QBO Sync Manager) financials';

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
		$companyId = $this->ask("Please enter company id: ");
		$company = Company::find($companyId);

		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}
		$customerId = $this->ask("Please enter customer id: ");;
		$customer = Customer::where('company_id', $companyId)
			->where('id', $customerId)
			->first();

		if(!$customer){
			$this->info('Please enter valid customer id.');
			return;
		}

		$this->info("Start Time: ".Carbon::now()->toDateTimeString());

		$systemUser = $company->anonymous;
		setAuthAndScope($systemUser->id);

		$jobInvoiceRepo = App::make(JobInvoiceRepository::class);
		$reason = 'Job:Map- Delete Job Financial';

		DB::beginTransaction();
		try{
			DB::table('job_invoices')
				->where('customer_id', $customerId)
				->where('reason', $reason)
				->update([
					'deleted_at' => null,
					'deleted_by' => null,
					'reason' => null,
				]);

			DB::table('job_payments')
				->where('customer_id', $customerId)
				->where('reason', $reason)
				->update([
					'deleted_at' => null,
					'deleted_by' => null,
					'reason' => null,
				]);

		$invoicePaymentsData = DeletedInvoicePayment::where('customer_id', $customerId)
			->where('company_id', $companyId)
			->get();

		$date = Carbon::now()->toDateTimeString();
		$jobIds = [];
		foreach ($invoicePaymentsData as $invoicePayment) {
			$paymentData = json_decode($invoicePayment->data);
			foreach ($paymentData as $payment) {
				$jobIds[] = $payment->job_id;
				$insertData = [
					'payment_id'=>$payment->payment_id,
					'job_id'=>$payment->job_id,
					'invoice_id'=>$payment->invoice_id,
					'credit_id'=>$payment->credit_id,
					'amount'=>$payment->amount,
					'created_at'=>$date,
					'updated_at'=>$date,
					'ref_id'=>$payment->ref_id,
				];
				DB::table('invoice_payments')->insert($insertData);
			}
		}

		if(!empty($jobIds)){
			$jobIds = arry_fu($jobIds);
			$jobs = Job::where('company_id', $companyId)
				->whereIn('id', $jobIds)
				->get();
			foreach ($jobs as $job) {
				$invoiceSum = $jobInvoiceRepo->getJobInvoiceSum($job->id);
				JobFinancialCalculation::updateJobInvoiceAmount($job,
					$invoiceSum->job_amount,
					$invoiceSum->tax_amount
				);
				JobFinancialCalculation::updateFinancials($job->id);

		        if($job->isProject() || $job->isMultiJob()) {
		            //update parent job financial
		            JobFinancialCalculation::calculateSumForMultiJob($job);
		        }
			}

		}

		$this->resynchCustomerAccount($customerId, QuickBookTask::SYSTEM_EVENT);
		} catch(Exception $e){
			DB::rollback();
			throw $e;
		}
		DB::commit();
		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}
