<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\JobInvoice;
use App\Models\JobInvoiceLine;

class MarkInvoiceLinesTaxable extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:mark_invoice_lines_taxable';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Make Invoice Lines Taxable Of Invoices Saved With Tax.';

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
		$startedAt = Carbon::now()->toDateTimeString();

		$this->info("Command started at: {$startedAt}");
		$totalInvoices = JobInvoice::where('taxable', true)->count();

		$this->info("Total invoices: {$totalInvoices}");

		$completedInvoices = 0;
		JobInvoice::where('taxable', true)
			->chunk(50, function($invoices) use($completedInvoices) {
				$invoiceIds = $invoices->pluck('id')->toArray();

				$completedInvoices += count($invoiceIds);

				JobInvoiceLine::whereIn('invoice_id', $invoiceIds)
					->update(['is_taxable' => true]);

			});

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("Total invoice completed: {$completedInvoices}");

		$pending = $totalInvoices - $completedInvoices;

		$this->info("Total invoice completed: {$pending}");

		$this->info("Command completed at: {$completedAt}");
	}
}
