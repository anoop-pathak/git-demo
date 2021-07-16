<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\JobFinancialCalculation;

class UpdateMultiJobFinancialCalculationsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_multi_job_financial_calculations';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Multi Job financial calcuation';

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
		$totalMultiJobs = Job::on('mysql2')->orderBy('id', 'desc')->where('multi_job', true)->count();
		$this->info('Total multi Jobs - '. $totalMultiJobs  . PHP_EOL);
		Job::orderBy('id', 'desc')->where('multi_job', true)->chunk(200, function($jobs) use(&$totalMultiJobs) {
			foreach ($jobs as $job) {
				setScopeId($job->company_id);
				JobFinancialCalculation::calculateSumForMultiJob($job);
				$this->info('Pending Jobs - '. --$totalMultiJobs  . PHP_EOL);
			}
		});
	}

}
