<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Job;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteDuplicateJobsOfPecificExteriors extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_duplicate_jobs_of_northern_pecific_exteriors';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->companyId = 1542;
		$this->systemUserId = 20119;
		$this->stageCode = 1339722300;
		$this->deleteJobCount = 5682;
		$this->createdAt = '2020-05-10';

		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->now = Carbon::now()->toDateTimeString();

		$this->info("--------- Command Started At: {$this->now} ----------------");

		$jobIds = Job::on('mysql2')
			->whereIn('id', function($query) {
			$query->select('job_id')
				->from('job_workflow')
				->where('company_id', $this->companyId)
				->where('current_stage', $this->stageCode);
		})
		->whereNull('archived')
		->where('company_id', $this->companyId)
		->where('created_by', $this->systemUserId)
		->whereDate('created_at', '=', $this->createdAt)
		->pluck('id')->toArray();

		$this->totalJobCount = count($jobIds);

		$this->info("Total Job counts to be processed: {$this->totalJobCount}");

		Job::on('mysql2')
			->whereIn('id', $jobIds)
			->where('company_id', $this->companyId)
			->where('created_by', $this->systemUserId)
			->whereDate('created_at', '=', $this->createdAt)
			->whereNull('archived')
			->chunk(50, function($jobs) {
				foreach ($jobs as $job) {
					DB::beginTransaction();
					try {
						$this->deleteJob($job);

						DB::commit();
						--$this->totalJobCount;
						$this->info("-------- Pending Jobs : {$this->totalJobCount} --------");
						$this->info("-------- Job Deleted : {$job->id} --------");
						
					} catch (Exception $e) {
						DB::rollback();
						$this->info("---- Job gets skipped : {$job->id} ----");
						$this->info("---- Error Details of job id: {$job->id} - ".$e->getMessage());
					}
				}
			});


		$this->info("---------- Total Pending Jobs : {$this->totalJobCount} ---------");
		$completedAt = Carbon::now()->toDateTimeString();
		
		$this->info("--------- Command Completed At: {$completedAt} ----------------");

	}

	private function deleteJob($job)
	{
		DB::table('activity_logs')
			->where('company_id', $this->companyId)
			->where('job_id', $job->id)
			->where('customer_id', $job->customer_id)
			->update([
				'public' => 0,
			]);

		DB::table("jobs")
			->where('id', $job->id)
			->where('company_id', $this->companyId)
			->update([
				'deleted_at' => $this->now,
				'deleted_by' => $this->systemUserId,
			]);
	}

}
