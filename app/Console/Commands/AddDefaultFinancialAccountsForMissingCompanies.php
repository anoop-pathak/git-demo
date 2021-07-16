<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Repositories\FinancialAccountRepository;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Exception;

class AddDefaultFinancialAccountsForMissingCompanies extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_financial_accounts_for_missing_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to add default financial accounts for the companies that are saved by Superadmin.';

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
		$this->canceledIds = [];
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$startedAt);

		$defaultAccounts = config('default-financial-accounts');
		$accountNames = array_column($defaultAccounts, 'name');

		$query = Company::with([
				'subscriber' => function($query) {
					$query->withTrashed();
				}
			])
			->whereNotIn('id', function($query) use($accountNames) {
				$count = count($accountNames);
				$query->select('company_id')
					->from('financial_accounts')
					->whereIn('name', $accountNames)
					->whereNull('deleted_at')
					->groupBy('company_id')
					->havingRaw("COUNT(*) = {$count}");
			});

		$this->info("\nTotal records to be processed: ".$query->count());

		$this->addFinancialAccounts($query);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\nCommand completed at: ".$completedAt);
	}

	private function addFinancialAccounts($query)
	{
		$query->whereNotIn('id', $this->canceledIds);

		$queryClone = clone $query;

		$financialAccRepo = app(FinancialAccountRepository::class);
		$queryClone->chunk(100, function($companies) use($financialAccRepo){
			foreach ($companies as $company) {
				DB::beginTransaction();
				try {
					$financialAccRepo->addDefaultFinancialAccountsForCompany(
						$company,
						$company->subscriber->id
					);

					DB::commit();
				} catch (Exception $e) {
					DB::rollback();

					$errMsg = getErrorDetail($e);
					$this->info("\nError occured for company id: ".$company->id. ".\nError detail: ".$errMsg);
					$this->canceledIds[] = $company->id;
				}
			}
		});

		$this->info("----- Pending records: ".$query->count()." -----");

		if($query->count()) {
			$this->addFinancialAccounts($query);
		}
	}

}
