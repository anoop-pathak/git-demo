<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Repositories\MeasurementRepository;
use App\Models\HoverJob;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Config;

class SystemMeasurementForHoverjob extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:system_measurement_for_hover_job';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'System Measurement For Hover Job Record';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->repo = app(MeasurementRepository::class);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		DB::beginTransaction();
		try {
			$startedAt = Carbon::now()->toDateTimeString();
			$this->info('Command Started At: '.$startedAt);
			$hoverJobs = HoverJob::select('hover_jobs.*')
				->leftJoin('measurements', 'measurements.hover_job_id', '=', 'hover_jobs.hover_job_id')
				->whereNull('measurements.hover_job_id')
				->with('hoverClient')
				->groupBy('id')
				->get();
			foreach ($hoverJobs as $key => $hoverJob) {
				Config::set('company_scope_id', $hoverJob->company_id);
				$meta['hover_job_id'] = $hoverJob->hover_job_id;
				$title = 'hover_job_'.$hoverJob->hover_job_id;
				$measurement = $this->repo->save($hoverJob->job_id,
					$title,
					$values = [],
					$hoverJob->hoverClient->created_by,
					$meta
				);
			}
			$ended_at = Carbon::now()->toDateTimeString();
			$this->info('Command Ended At: '.$ended_at);
			DB::commit();

		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}
	}
}
