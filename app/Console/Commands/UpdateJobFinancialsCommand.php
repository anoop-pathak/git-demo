<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\JobFinancialCalculation;
use Carbon\Carbon;

class UpdateJobFinancialsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_job_financials';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Job Financials By Job Id';

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
		$jobId =  $this->ask('Enter Job id:');
		$job = Job::find($jobId);

		if(!$job){
			$this->info('Please enter valid job id.');
			return;
		}
		$this->info("Start Time: ".Carbon::now()->toDateTimeString());

		setScopeId($job->company_id);
		JobFinancialCalculation::updateFinancials($job->id);

        if($job->isProject() || $job->isMultiJob()) {
            //update parent job financial
            JobFinancialCalculation::calculateSumForMultiJob($job);
        }

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}
