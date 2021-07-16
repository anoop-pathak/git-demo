<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\FinancialCategory;

class AddComapnayInsuranceCategory extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_company_insurance_category';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add Insurance category for all companies.';

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
		$start = Carbon::now()->toDateTimeString();
		$this->info("Command Started At {$start}");

		$this->count = 0;

		Company::withTrashed()->chunk(10, function($companies) {
			foreach ($companies as $company) {

				$category = FinancialCategory::firstOrNew([
					'name' => FinancialCategory::INSURANCE,
					'company_id' => $company->id,
				]);
				$category->default 	= 1;
				$category->order 	= 0;
				$category->slug 	= strtolower(str_replace(' ', '_', FinancialCategory::INSURANCE));
				$category->save();
				$this->count++;
			}
		});

		$end = Carbon::now()->toDateTimeString();
		$this->info("Category added for {$this->count} company(s)");
		$this->info("Command Completed At {$end}");
	}
}