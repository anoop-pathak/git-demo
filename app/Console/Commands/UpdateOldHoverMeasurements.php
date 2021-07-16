<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Measurement\MeasurementService;
use App\Models\HoverJob;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOldHoverMeasurements extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_old_hover_measurements';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will update old hover measurement.';

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
		$measurementService = app(MeasurementService::class);
		$hoverJobs = HoverJob::where('deliverable_id', '<>', HoverJob::DELIVERABLE_TYPE_HOVER_NOW_ID)
			->where('state', HoverJob::COMPLETE)
			->where('updated_at', '<', '2020-12-30 13:00:00')
			->with(['measurement', 'jsonReport'])
			->where('company_id', '!=', 12)
			->select('hover_jobs.*');

		$totalRecords = $hoverJobs->count();

		$this->info("Total records: ".$totalRecords);

		$hoverJobs->chunk(100, function($hoverjobs) use ($measurementService, &$totalRecords){
			foreach ($hoverjobs as $hoverJob) {
				if($hoverJob->measurement && $hoverJob->jsonReport) {

					DB::beginTransaction();
					try {
						$jsonReport = $hoverJob->jsonReport;
						setScopeId($hoverJob->company_id);
						$filePath = config('jp.BASE_PATH').$jsonReport->file_path;
						$measurementService->updateHoverMeasurement($hoverJob->measurement, $filePath);


						$this->info("Pending records: ".--$totalRecords);
						$hoverJob->touch();
						DB::commit();
					} catch (Exception $e) {
						DB::rollback();
						$msg = getErrorDetail($e);
						Log::info("Error occurred: ".$msg);
						$this->info( 'Error Occured... HOVER Job Id: '. $hoverJob->id .' Message: '. $msg);
					}
				} else {
					$this->info('Measurement Not Found: .'. $hoverJob->id);
				}

			}
		});
	}

}
