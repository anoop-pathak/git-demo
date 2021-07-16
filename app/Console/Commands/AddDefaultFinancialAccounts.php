<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\FinancialAccount;

class AddDefaultFinancialAccounts extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_financial_accounts';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add default financial accounts for particular company';

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

		$financialAccounts = [
			[
				'name'				=>	'Permits',
				'account_type'		=>	'Expense',
				'account_sub_type'	=>	'OtherMiscellaneousServiceCost',
				'classification'	=>	'EXPENSE'
			],
			[
				'name'			    =>	'Warranties',
				'account_type'	    =>	'Long Term Liability',
				'account_sub_type'  =>	'OtherLongTermLiabilities',
				'classification'    =>	'LIABILITY'
			]
		];

		$companyId = $this->ask('Please Enter Company Id: ');
		$company = Company::find($companyId);

		if(!$company) {
			$this->error('Invalid Company Id.');
			return;
		}

		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: {$startedAt}");

		$user = $company->subscriber;
		$userId = $user->id;

		$now = Carbon::now();
		foreach ($financialAccounts as $financialAccount) {
			$account = FinancialAccount::where('company_id', $companyId)->where('name', '=', $financialAccount['name'])->first();
			if($account) continue;

			$data = [
				'company_id'		=> $companyId,
				'name'		    	=> $financialAccount['name'],
				'account_type'		=> $financialAccount['account_type'],
				'account_sub_type'	=> $financialAccount['account_sub_type'],
				'classification'	=> $financialAccount['classification'],
				'created_by'		=> $userId,
				'updated_by'		=> $userId,
				'created_at' 		=> $startedAt,
				'updated_at' 		=> $startedAt,
			];
			FinancialAccount::create($data);
		}

		$completedAt = Carbon::now();
		$this->info("Command completed at: {$completedAt}");
    }
}
