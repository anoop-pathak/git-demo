<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
class SRSMaterialsOrderDetail extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:srs_materials_order_details';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get srs material order details.';
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
		$companyId = $this->ask('CompanyId:');
		$materialListId = $this->ask(PHP_EOL.'material List Id:');
 		setScopeId($companyId);
		$data = [
			'material_list_id' => $materialListId
		];
		$service = \App::make('App\Services\SRS\SRSService');
		$supplier = Supplier::whereName('SRS')->whereNull('company_id')->first();
		$response = $service->getPriceList($supplier, $data);
		
		$this->info(PHP_EOL. json_encode($response, true));
	}
 }