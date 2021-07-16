<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
 class ConnectSRSAccount extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:connect_srs_account';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Connect SRS using invoice details or customer account number.';
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
		// $this->info("Connect SRS using Customer Account number or Invoice Details \n");
		$connectBy = $this->confirm('Connect SRS using Account number? [yes|no]');
		$companyId = $this->ask('Company Id:');
		$company = Company::findOrFail($companyId);
 		setScopeId($companyId);
 		$srsSupplier = Supplier::whereName(Supplier::SRS_SUPPLIER)
			->whereNull('company_id')
			->firstOrFail();
 		if ($srsSupplier->companySupplier) {
			$this->info("SRS already connected.");
 			return true;
		}
 		$this->srsService = \App::make('App\Services\SRS\SRSService');
 		if ($connectBy) {
			$accountNumber = $this->ask('Account Number:');
 			$this->connectByAccountNumber($accountNumber);
		}else {
			$invoiceNumber = $this->ask('Invoice Number:');
			$invoiceDate   = $this->ask('Invoice Date:');
			$accountNumber = $this->ask('Account Number:');
 			$this->connectByInvoice($accountNumber, $invoiceNumber, $invoiceDate);
		}
 		$this->info("SRS connected successfully");
	}
 	private function connectByAccountNumber($accountNumber)
	{
		$data = [
			'account_number' => $accountNumber,
		];
 		$this->srsService->connectByAccountNumber($data);
	}
 	private function connectByInvoice($accountNumber, $invoiceNumber, $invoiceDate)
	{
		$data = [
			'account_number' => $accountNumber,
			'invoice_number' => $invoiceNumber,
			'invoice_date'	 => $invoiceDate
		];
 		$this->srsService->connect($data);
	}
}