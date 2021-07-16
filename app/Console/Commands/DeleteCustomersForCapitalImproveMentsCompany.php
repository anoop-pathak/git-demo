<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Customer;

class DeleteCustomersForCapitalImproveMentsCompany extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_customers_for_capital_improvements_company';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Commad to delete customers created on 2nd October of Capital Improvements Company.';

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
		$companyId = 1948;
		$company = Company::findOrFail($companyId);
		$systemUser = $company->anonymous;

		$startId = 1716842;
		$endId = 1718653;

		$customerIds = Customer::where('company_id', $companyId)
			->whereBetween('id', [$startId, $endId])
			->whereDate('created_at', '=', '2020-10-02')
			->pluck('id')->toArray();

		$this->info("----- Start Time: ".Carbon::now()->toDateTimeString(). " -----\n");

		Customer::whereIn('id', $customerIds)
			->where('company_id', $companyId)
			->update([
				'deleted_at' => Carbon::now()->toDateString(),
				'deleted_by' => $systemUser->id,
				'delete_note' => "Deleted due to duplicacy.",
			]);

		$this->info('----- Total Customer Delete: '.count($customerIds). " -----\n");

		foreach ($customerIds as $customerId) {
			Solr::customerJobDelete($customerId);
		}

		$this->info("----- End Time: ".Carbon::now()->toDateTimeString(). " -----");
	}

}
