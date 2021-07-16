<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use App\Models\QuickBookConnectionHistory;
use App\Models\QBOBill;
use App\Models\Setting;
use App\Models\QuickBookTask;
use Carbon\Carbon;

class UnlinkAllQBOEntitiesOfCompany extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:unlink_all_qbo_entities_of_company';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Unlink all QBO entities of a company.';

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
		$companyId = $this->ask("Please enter company id for which you want to clean QBO records: ");

		$company = Company::findOrFail($companyId);

		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: {$startedAt} -----");

		$this->cleanAllQBOEntities($companyId);
		$this->cleanQBOSyncRequestsData($companyId);

		$this->cleanDeletedQBOEntityDump($companyId);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\n----- Command completed at: {$completedAt} -----");
	}

	private function cleanAllQBOEntities($companyId)
	{
		DB::table('jobs')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Jobs Updated. -----");

		DB::table('customers')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Customers Updated. -----");

		DB::table('job_invoice_lines')
			->join('job_invoices', 'job_invoices.id', '=', 'job_invoice_lines.invoice_id')
			->join('jobs', 'jobs.id', '=', 'job_invoices.job_id')
			->where('jobs.company_id', $companyId)
			->update([
				'qb_txn_line_id' => null,
				'qb_item_id' => null,
			]);
		$this->info("\n----- Job Invoice Lines Updated. -----");

		DB::table('job_invoices')
			->join('jobs', 'jobs.id', '=', 'job_invoices.job_id')
			->where('jobs.company_id', $companyId)
			->update([
				'job_invoices.quickbook_invoice_id' => null,
				'job_invoices.quickbook_sync_token' => null,
				'job_invoices.quickbook_sync_status' => null,
			]);
		$this->info("\n----- Job Invoices Updated. -----");

		DB::table('vendors')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Vendors Updated. -----");

		DB::table('vendor_bills')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Financial Accounts Updated. -----");

		DB::table('vendor_bills')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Vendor Bills Updated. -----");

		DB::table('job_payments')
			->join('jobs', 'jobs.id', '=', 'job_payments.job_id')
			->where('jobs.company_id', $companyId)
			->update([
				'job_payments.quickbook_id' => null,
				'job_payments.quickbook_sync_token' => null,
				'job_payments.quickbook_sync_status' => null,
			]);
		$this->info("\n----- Job Payments Updated. -----");

		DB::table('job_credits')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Job Credits Updated. -----");

		DB::table('job_refunds')
			->where('company_id', $companyId)
			->update([
				'quickbook_id' => null,
				'quickbook_sync_token' => null,
				'quickbook_sync_status' => null,
			]);
		$this->info("\n----- Job Refunds Updated. -----");

		DB::table('job_types')
			->where('company_id', $companyId)
			->update([
				'qb_id' => null,
				'qb_account_id' => null,
			]);
		$this->info("\n----- Job Types Updated. -----");

		DB::table('job_types')
			->where('company_id', $companyId)
			->update([
				'qb_id' => null,
			]);
		$this->info("\n----- Divisions Updated. -----");

		QuickBookConnectionHistory::where('company_id', $companyId)->delete();
		$this->info("\n----- Quickbook Connection History Deleted. -----");

		QBOBill::where('company_id', $companyId)->delete();
		$this->info("\n----- QBO Bills Deleted. -----");

		Setting::where('company_id', $companyId)->where('key', 'QBO_ITEMS')->delete();
		$this->info("\n----- QBO Items Deleted From Settings. -----");
	}

	private function cleanQBOSyncRequestsData($companyId)
	{
		DB::table('quickbook_sync_tasks')
			->where('company_id', $companyId)
			->where('status', QuickBookTask::STATUS_PENDING)
			->update([
				'status' => QuickBookTask::STATUS_ERROR,
				'msg'	 => "QBO data cleanup",
			]);
		$this->info("\n----- QBO Sync Tasks Updated. -----");

		DB::table('quickbook_sync_customers')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- QBO Sync Customers Deleted. -----");

		DB::table('quickbook_sync_batches')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Quickbook Sync Requests Deleted. -----");

		DB::table('quickbook_unlink_customers')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Quickbook Sync Requests Unlinked Customer records Deleted. -----");

		DB::table('qb_entity_errors')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Quickbook Entity Errors Records Deleted. -----");

		DB::table('quickbooks_activity')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Quickbook Activty Records Deleted. -----");
	}

	private function cleanDeletedQBOEntityDump($companyId)
	{
		DB::table('deleted_invoice_payments')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Invoice Payments Table Records Deleted. -----");

		DB::table('deleted_quickbook_bills')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Bills Table Records Deleted. -----");

		DB::table('deleted_quickbook_credits')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Credits Table Records Deleted. -----");

		DB::table('deleted_quickbook_invoices')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Invoices Table Records Deleted. -----");

		DB::table('deleted_quickbook_payments')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Payments Table Records Deleted. -----");

		DB::table('deleted_quickbook_refunds')
			->where('company_id', $companyId)
			->delete();
		$this->info("\n----- Deleted Refunds Table Records Deleted. -----");
	}

}
