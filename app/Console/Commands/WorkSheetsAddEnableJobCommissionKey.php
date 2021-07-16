<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkSheetsAddEnableJobCommissionKey extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_enable_job_commission_key_in_profit_loss';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add Key Of Enable Job Commission For Profit And Loss worksheet';

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
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: {$startedAt} -----");

		DB::statement("UPDATE worksheets SET worksheets.enable_job_commission = 1 where worksheets.type = 'profit_loss'");

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\n----- Command completed at: {$completedAt} -----");
	}

}
