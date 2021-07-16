<?php namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobFinancialCalculation;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
class UpdateJobFinancialCalculationsCommand extends Command {

 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_job_financial_calculations';

 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add total received payment on invoices and change order on invoices in job financial calculations';

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
 		Job::orderBy('id', 'desc')->chunk(200, function($jobs){
			foreach ($jobs as $job) {
				setScopeId($job->company_id);
				JobFinancialCalculation::updateFinancials($job->id);
			}
		});
	}
 }