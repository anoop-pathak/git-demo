<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Repositories\FinancialAccountRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class AddDefaultFinancialAccountsForAllCompanies extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_financial_accounts_for_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add default financial accounts for all companies.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->repo = app(FinancialAccountRepository::class);

		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		Company::chunk(50, function($companies) {
				foreach ($companies as $company) {
					$this->addFinancialAccounts($company);
				}
			});
	}

	private function addFinancialAccounts($company)
	{
		try {
			$this->repo->addDefaultFinancialAccountsForCompany($company, 1);
			$this->info("Added Company Id: {$company->id}");
		} catch (Exception $e) {
			Log::error($e);

			$this->info("Error on adding financial account. Company Id: {$company->id} - ". $e->getMessage());
		}
	}
}
