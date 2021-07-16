<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Measurement;
use App\Models\EVReport;

class UpdateQuickSquareMeasurementReport extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_quick_square_measurement_report';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Quick Square Measurement Report.';
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
	public function handle()
	{
        $measurements = Measurement::WhereNotNull('ev_report_id')
            ->join('ev_reports', 'ev_reports.report_id', '=', 'measurements.ev_report_id')
            ->where('ev_reports.file_type_id', EVReport::QUICK_SQUARE_REPORT_ID)
            ->where('total_values', 0)
            ->where('ev_reports.file_mime_type', 'text/x-json')
            ->select('measurements.*')
            ->get();
         $this->info("Total Measurement Count: ". $totalMeasuremens = $measurements->count());
        foreach ($measurements as $measurement) {
            $this->info("Pending Measurement Count: ". $totalMeasuremens-- . PHP_EOL);
            $this->saveMeasurementValues($measurement);
        }
    }
    
    private function saveMeasurementValues($measurement)
    {
        try {
            $service = \App::make('App\Services\Measurement\MeasurementService');
            $report = $measurement->evOrder->allReports()->where('file_mime_type', EVReport::JSON)->first();
            $filePath = $report->file_path;
            $measurement->timestamps = false;
            $service->updateEagleviewMeasurement($measurement,  $filePath);
        } catch(Exception $e) {
            $errorMessage = PHP_EOL .'Error: Measurement Id: '.$measurement->id . ' , Error Detail: '. getErrorDetail($e) . PHP_EOL;
            \Log::warning($errorMessage);
            $this->info($errorMessage);
        }
    }
    
}