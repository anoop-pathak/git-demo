<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Trade;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\Measurement;
use FlySystem;
use DataMasking;
use PDF;

class CreateMeasurementSubPrints extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_measurement_sub_prints';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create measurement prints for sub contractors.';
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
		$measurementList = Measurement::whereNull('ev_report_id')
				->has('company')
				->has('job')
				->whereNull('sm_order_id')
				->get();
 		DataMasking::enable();
 		foreach ($measurementList as $key => $measurement) {
			$this->createPdf($measurement);
		}
 		DataMasking::disable();
	}
 	private function createPDF($measurement)
	{
		$baseName = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $measurement->file_path);
		$fullPath = config('jp.BASE_PATH').$baseName;
		$job = $measurement->job;
		$measurementDetail = $this->getMeasurementDetails($measurement);
 		$pageHeight = '23.9cm';
 		if($measurement->page_type == 'legal-page') {
			$pageHeight = '28.6cm';
		}
 		$pdf = PDF::loadView('jobs.measurement', [
			'job'		  => $job,
			'company'     => $job->company,
			'customer'    => $job->customer,
			'measurement' => $measurementDetail,
			'country'     => $job->company->country,
		])
		->setOption('dpi', 200)
		->setOption('page-size','A4')
		->setOption('page-width','16.8cm')
		->setOption('page-height', $pageHeight);
 		$mimeType = 'application/pdf';
		FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
	}
 	private function getMeasurementDetails($measurement)
	{
		$company = Company::find($measurement->company_id);
		$companyId = $company->id;
		$tradeIds  = $company->trades()->select('trades.id')->pluck('trades.id')->toArray();
 		$trades = Trade::whereIn('trades.id', $tradeIds)->join('measurement_values', 'measurement_values.trade_id', '=', 'trades.id')->with([
				'measurementValues' => function($query) use($measurement) {
					$query->join('measurement_attributes', 'measurement_attributes.id', '=', 'measurement_values.attribute_id');
					$query->where('measurement_id', $measurement->id)
						  ->select('measurement_values.trade_id', 'attribute_id', 'value', 'name', 'slug');
				}
			])->where('measurement_values.measurement_id', $measurement->id)
			  ->groupBy('trades.id')	
			  ->select('trades.*')
			  ->get();
 		if(!$trades) return false;
 		$measurement->trades = $trades;
 		return $measurement;
	}
}