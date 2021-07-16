<?php
namespace App\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\EVReport;
use App\Models\Measurement;

class CreateEagleViewMeasurementByReportIds extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_eagleview_measurement_by_report_ids';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create measurement for eagleview reports by report ids.';
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
		$reportIds = $this->ask('Please enter report ids.');
		$reportIds = explode(',', $reportIds);
		$measurementService = \App::make('App\Services\Measurement\MeasurementService');
 		$reports = EVReport::where('file_mime_type', EVReport::JSON)->whereIn('report_id', $reportIds)->get();
 		foreach ($reports as $report) {
			$measurement = Measurement::where('ev_report_id', $report->report_id)->first();
			$measurementService->updateEagleviewMeasurement($measurement, $report->file_path, $report->file_type_id);
			$this->info($report->report_id.' successfully updated');
		}
	}
}