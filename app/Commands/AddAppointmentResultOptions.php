<?php

namespace App\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentResultOption;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AddAppointmentResultOptions extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_appointment_result_options';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add default appointment result options for companies.';
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
		Company::withTrashed()->chunk(50, function($companies) {
			foreach ($companies as $key => $company) {
				$this->saveResultOptions($company);
			}
		});
	}
 	private function saveResultOptions($company)
	{
		$resultOptions = [
			0 => [
				'name' => 'Sold',
				'fields' => [
					0 => [
						'name' => 'Product',
						'type' => 'text'
					],
					1 => [
						'name' => 'Price',
						'type' => 'text'
					],
				]
			],
			1 => [
				'name' => 'DNS',
				'fields' => [
					0 => [
						'name' => 'Product',
						'type' => 'text'
					],
					1 => [
						'name' => 'Reason',
						'type' => 'textarea'
					],
				]
			],
			2 => [
				'name' => 'One Legger',
				'fields' => [
					0 => [
						'name' => 'Who was missing',
						'type' => 'text'
					],
					1 => [
						'name' => 'Was it reset?',
						'type' => 'text'
					],
				]
			],
			3 => [
				'name' => 'Not Home',
				'fields' => [
					0 => [
						'name' => 'Did you speak it',
						'type' => 'text'
					],
					1 => [
						'name' => 'Was it reset?',
						'type' => 'text'
					],
				]
			],
		];
 		AppointmentResultOption::whereIn(DB::raw("BINARY(name)"), ['sold', 'dns', 'one_legger', 'not_home'])->forceDelete();
 		foreach ($resultOptions as $key => $value) {
			$option = AppointmentResultOption::firstOrNew([
				'company_id' => $company->id,
				'name' 		 => $value['name'],
			]);
 			$option->created_by = ($subscriber = $company->subscriber) ? $subscriber->id : 0;
			$option->fields = $value['fields'];
			$option->save();
		}
 	}
}