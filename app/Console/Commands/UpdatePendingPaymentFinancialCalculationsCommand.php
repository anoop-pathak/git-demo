<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdatePendingPaymentFinancialCalculationsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_pending_payment_financial_calculations';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Pending Payment financial calcuation';

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

		DB::statement("UPDATE job_financial_calculations
			SET  pending_payment = (total_job_amount - (IFNULL(total_credits, 0) + IFNULL(total_refunds, 0) + IFNULL(total_received_payemnt, 0)))"
		);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\n----- Command completed at: {$completedAt} -----");
	}
}
