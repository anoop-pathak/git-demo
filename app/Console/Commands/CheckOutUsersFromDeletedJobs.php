<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Job;
use Illuminate\Support\Facades\Queue;

class CheckOutUsersFromDeletedJobs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:check_out_users_from_deleted_jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Users will be check out from the deleted jobs by executing this command.';

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
		$this->info("Command started at: ".$startedAt);

		$jobs = Job::onlyTrashed()
			->whereIn('id', function($subQuery) {
				$subQuery->selectRaw('distinct job_id')
					->from('timelogs')
					->whereNotNull('start_date_time')
					->whereNull('end_date_time');
			})
			->get();

		$totalRecords = $jobs->count();

		$this->info("\nTotal jobs will be pushed in queue: {$totalRecords}\n");

		foreach($jobs as $job) {
			$data = [
				'job_id' => $job->id,
			];

			Queue::push('\App\Handlers\Events\DeletedJobsTimeLogQueueHandler', $data);

			$totalRecords--;

			$this->info("----- Pending Records: {$totalRecords} -----");
		}

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\nCommand completed at: ".$completedAt);
	}
}