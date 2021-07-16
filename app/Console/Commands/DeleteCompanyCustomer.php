<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Solr;
use Exception;

class DeleteCompanyCustomer extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_company_customers';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete customer and jobs of specific company';

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
		EnterPassword : $password = $this->secret('What is the password?');
		if($password != config('jp.developer_secret')) {
			$this->error('Incorrect Password.');
			goto EnterPassword;
		}

		$companyId = $this->ask('Please enter company id of the account that you want to do reset: ');
		$deletedAt = $startedAt = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: $startedAt -----");

		$company = Company::findOrFail($companyId);

		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		DB::beginTransaction();
		try {
			DB::table('jobs')
			->where('company_id', $companyId)
			->delete();
			$this->info("----- Jobs Deleted -----");

			DB::table('customers')
			->where('company_id', $companyId)
			->delete();
			$this->info("----- Customers Deleted -----");

			Solr::allCustomersDelete($companyId);
			$this->info("----- Customer and Jobs deleted from SOLR -----");

		} catch (Exception $e) {
			DB::rollback();
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');
			throw $e;
		}

		DB::commit();
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("----- Command completed at: $completedAt -----");
	}
}
