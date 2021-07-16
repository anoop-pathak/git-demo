<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\JobWorkflowHistory;
use Illuminate\Support\Facades\DB;

class DeleteDuplicateWorkflowHistoryStage extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_duplicate_entries_from_wfh';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Delete duplicate entries fron job workflow history table. It is creating issue in Account Receivable Report';

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
		$startedAt = Carbon::now()->toDatetimeString();

		$this->info("Command started at: ".$startedAt);

		$duplicateJobStages = JobWorkflowHistory::groupBy('stage', 'job_id', 'company_id')
			->havingRaw("COUNT(*) > 1")
			->get();

		$totalRecords = $duplicateJobStages->count();

		$this->info("Total records to be updated: {$totalRecords}");

		foreach ($duplicateJobStages as $duplicateJobStage) {
			$stage = $duplicateJobStage->stage;
			$jobId = $duplicateJobStage->job_id;
			$duplicateStage = JobWorkflowHistory::where('stage', $stage)
				->where('job_id', $jobId)
				->first();

			$startDate = $duplicateStage->start_date;
			$completedDate = $duplicateStage->completed_date;

			$prevStage = JobWorkflowHistory::where('id', '<', $duplicateStage->id)
				->where('job_id', $jobId)
				->orderBy('id', 'desc')
				->first();

			$nextStage = JobWorkflowHistory::where('id', '>', $duplicateStage->id)
				->where('stage', '<>', $stage)
				->where('job_id', $jobId)
				->orderBy('id', 'asc')
				->first();


			if($prevStage) {
				$startDate = $prevStage->completed_date;
			}
			if($nextStage) {
				// check if next stage is also same as duplicate stage
				$sameNextStage = JobWorkflowHistory::where('id', '>', $duplicateStage->id)
					->where('job_id', $jobId)
					->orderBy('id', 'asc')
					->first();
				if($sameNextStage
					&& $sameNextStage->stage == $duplicateStage->stage
					&& $nextStage->start_date != $sameNextStage->start_date ) {

					DB::table('job_workflow_history')
						->where('id', $nextStage->id)
						->update([
							'start_date' => $sameNextStage->start_date,
						]);
				}

				$completedDate = $nextStage->start_date;
			}

			if(($startDate != $duplicateStage->start_date) || ($completedDate != $duplicateStage->completed_date)) {

				DB::table('job_workflow_history')
					->where('id', $duplicateStage->id)
					->update([
						'start_date' => $startDate,
						'completed_date' => $completedDate
					]);
			}

			JobWorkflowHistory::where('id', '<>', $duplicateStage->id)
				->where('stage', $stage)
				->where('job_id', $jobId)
				->delete();

			--$totalRecords;

			$this->info("--------- Pending records: {$totalRecords} ---------");
		}

		$completedAt = Carbon::now()->toDatetimeString();

		$this->info("Command completed at: ".$completedAt);
	}

}
