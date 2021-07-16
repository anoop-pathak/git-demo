<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\FinancialAccount;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class AddDefaultRefundsAccount extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_refund_accounts_for_existing_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add default financial accounts for existing companies.';

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
		Company::chunk(50, function($companies) {
				foreach ($companies as $company) {
					$this->addFinancialAccounts($company);
				}
			});
	}

	private function addFinancialAccounts($company)
	{
		try {
			$this->addDefaultFinancialAccountsForCompany($company, 1);
			$this->info("Added Company Id: {$company->id}");
		} catch (Exception $e) {
			Log::error($e);

			$this->info("Error on adding financial account. Company Id: {$company->id} - ". $e->getMessage());
		}
	}

	public function addDefaultFinancialAccountsForCompany($company, $createdBy)
	{
		$defaultAccounts = [
			[
				'name'				=> "Undeposited Funds",
				'account_type'		=> 'Other Current Assets',
				'account_sub_type'	=> 'UndepositedFunds',
				'classification'	=> 'ASSET',
			],
			[
				'name'				=> "Uncategorized Asset",
				'account_type'		=> 'Other Current Assets',
				'account_sub_type'	=> 'OtherCurrentAssets',
				'classification'	=> 'ASSET',
			],
			[
				'name'				=> "Inventory Asset",
				'account_type'		=> 'Other Current Assets',
				'account_sub_type'	=> 'Inventory',
				'classification'	=> 'ASSET',
			]
		];

		$data = [];
		$companyAccounts = FinancialAccount::where('company_id', $company->id)->pluck('name')->toArray();

		foreach ($defaultAccounts as $value) {

			if(in_array($value['name'], $companyAccounts)) {
				continue;
			}
			$now = Carbon::now()->toDateTimeString();

			$value['company_id'] = $company->id;
			$value['created_by'] = $createdBy;
			$value['updated_by'] = $createdBy;
			$value['created_at'] = $now;
			$value['updated_at'] = $now;

			$data[] = $value;
		}

		if(!empty($data)) {
			FinancialAccount::insert($data);
		}
	}
}
