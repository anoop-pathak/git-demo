<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\FinancialMacro;
use Illuminate\Support\Facades\DB;

class AddOrderForFinancialMacros extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_order_for_old_macros';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add order for old macros';

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

		$companyIds = Company::pluck('id')->toArray();
		foreach($companyIds as $compnayId) {

			$order = 1;

			$OldMacros = FinancialMacro::whereCompanyId($compnayId)
				->orderBy('id', 'asc')
				->select('id')
				->chunk(100, function($OldMacros) use(&$order)
			{
				foreach($OldMacros as $key => $macro) {

					DB::table('financial_macros')->whereId($macro->id)
						->update(['order' => $order]);

					$order++;
				}
			});
		}
		$end = Carbon::now()->toDateTimeString();
		$this->info("Command Completed At {$end}");
    }
}
