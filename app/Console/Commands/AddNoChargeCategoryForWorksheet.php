<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialCategory;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Carbon\Carbon;

class AddNoChargeCategoryForWorksheet extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_no_charge_category';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create no charge category for worksheets.';
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
		$order 		 = 0;
		$now 		 = Carbon::now()->toDateTimeString();
		$companyList = Company::all();
 		foreach ($companyList->chunk(100) as $companies) {
			foreach ($companies as $key => $company) {
 				$alreadyExist = FinancialCategory::whereCompanyId($company->id)
					->whereName(FinancialCategory::NO_CHARGE)
					->first();
 				if($alreadyExist) continue;
 				$lastCategory = FinancialCategory::whereCompanyId($company->id)
					->orderBy('id', 'desc')
					->first();
 				if($lastCategory) $order = $lastCategory->order + 1;
 				$data[] = [
					'name'		 => FinancialCategory::NO_CHARGE,
					'default'	 => true,
					'company_id' => $company->id,
					'created_at' => $now,
					'updated_at' => $now,
					'order' 	 => $order,
					'slug' 	 	 => strtolower(str_replace(' ', '_', FinancialCategory::NO_CHARGE)),
				];
			}
		}
 		if(isset($data)) {
			FinancialCategory::insert($data);
		}
	}
}
