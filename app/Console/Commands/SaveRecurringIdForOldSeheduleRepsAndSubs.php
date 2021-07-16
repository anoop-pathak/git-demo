<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobSchedule;
use Carbon\Carbon;

class SaveRecurringIdForOldSeheduleRepsAndSubs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_recurring_id_in_schedule_reps_and_subs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add Recurrings Id In Job Reps And Subcontractor Table For Old Schedules.';

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
		$processed = 0;
		$startedAt = Carbon::now();
		$this->info("----- Command started at: {$startedAt} -----");
		$builder = new JobSchedule;
		$builder->with(['reps', 'subContractors']);
		$total = $builder->count();
		$this->info("----- Total Records: {$total} -----");

		$builder->chunk(100, function($JobSchedules) use($total, &$processed) {
			foreach ($JobSchedules as $JobSchedule) {
				$this->saveRepsAndSubs($JobSchedule);
				$processed ++;
				$this->info("Processed data is " . $processed. " / " . $total);
			}
		});

		$completedAt = Carbon::now();
		$this->info("----- Command completed at: {$completedAt} -----");
	}

	public function saveRepsAndSubs($JobSchedule)
	{
		$recurrings = $JobSchedule->recurrings;
		$reps = array_unique($JobSchedule->reps->pluck('id')->toArray());
		$subs = array_unique($JobSchedule->subContractors->pluck('id')->toArray());
		$jobId = ($JobSchedule->job_id) ? : 0;

		// $JobSchedule->reps()->detach();
		// $JobSchedule->subContractors()->detach();

		foreach ($recurrings as $recurring) {
			$data = [
				'schedule_id'  => $JobSchedule->id,
				'recurring_id' => $recurring->id,
				'job_id' => $jobId
			];

			if ($reps) {
				$JobSchedule->reps()->attach($reps, $data);
			}

			if ($subs) {
				$JobSchedule->subContractors()->attach($subs, $data);
			}
		}
	}
}
