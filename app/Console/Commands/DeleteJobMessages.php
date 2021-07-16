<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\MessageThread;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class DeleteJobMessages extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_job_messages';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete all messages of a job';

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
		$companyId = $this->ask('Please enter company id.');

		$company = Company::findOrFail($companyId);

		$jobId = $this->ask("Please enter job id.");

		$job = Job::where('id', $jobId)
			->where('company_id', $company->id)
			->firstOrFail();

		$confirmed = $this->confirm("Please make sure company id and job id are fine? Y|N");

		if(!$confirmed) {
			$this->info("Please enter details again");
			return;
		}

		$startedAt = Carbon::now()->toDateTimeString();

		$this->info("Command started at: $startedAt");

		$threads = MessageThread::where('company_id', $companyId)
			->where('job_id', $jobId)
			->with('participants')
			->get();

		$pendingThreads = $totalThreads = $threads->count();

		$this->info("--------- Total threads to be deleted: {$totalThreads} ---------");

		$systemUser = $company->anonymous;

		$count = 1;

		$deletedAt = Carbon::now()->toDateTimeString();

		foreach ($threads as $value) {
			$participants = $value->participants;

			DB::table('messages')
				->where('thread_id', $value->id)
				->whereNull('deleted_at')
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $systemUser->id,
				]);

			DB::table('message_threads')
				->where('id', $value->id)
				->whereNull('deleted_at')
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $systemUser->id,
				]);

			Queue::push('\App\Handlers\Events\MessagesQueueHandler@updateFirebaseMessageCount', [
				'current_user_id' => $systemUser->id,
				'participant_ids' => $participants->pluck('id')->toArray(),
			]);

			--$pendingThreads;

			$this->info("--------- Pending threads : {$pendingThreads} ---------");

			$count++;
		}

		$completeddAt = Carbon::now()->toDateTimeString();

		$this->info("Command completed at: $completeddAt");
	}

}
