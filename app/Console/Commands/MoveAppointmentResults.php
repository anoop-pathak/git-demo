<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\AppointmentResultOption;
use App\Models\AppointmentRecurring;

class MoveAppointmentResults extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_appointment_results';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Move appointment results to appointments table from appointments recurring.';
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
		AppointmentRecurring::withTrashed()->whereNotNull('result')->chunk(100, function($appointments) {
			foreach ($appointments as $key => $appointment) {
				$this->moveResult($appointment);
			}
		});
	}

 	private function moveResult($recAppointment)
	{
		$appointment = Appointment::withTrashed()->find($recAppointment->appointment_id);
 		if(!$appointment) return false;
 		$name = $this->getResultName($recAppointment->result);
 		$activeOptions = AppointmentResultOption::where('company_id', $appointment->company_id)
			->where('active', true)
			->pluck('id')->toArray();
 		$resultOption = AppointmentResultOption::where('company_id', $appointment->company_id)
			->where('name', $name)
			->first();
 		if(!$resultOption) return false;
 		$appointment->result_option_id = $resultOption->id;
		$appointment->result = $this->getResult($name, $recAppointment->result_text1, $recAppointment->result_text2);
		$appointment->result_option_ids = $activeOptions;
		$appointment->save();
	}

 	private function getResult($key, $value1, $value2)
	{
		$fields = [
			'Sold' => [
				0 => [
					'name' => 'Product',
					'type' => 'text',
					'value' => $value1,
				],
				1 => [
					'name' => 'Price',
					'type' => 'text',
					'value' => $value2,
				],
			],
			'DNS' => [
				0 => [
					'name' => 'Product',
					'type' => 'text',
					'value' => $value1,
				],
				1 => [
					'name' => 'Reason',
					'type' => 'textarea',
					'value' => $value2,
				],
			],
			'One Legger' => [
				0 => [
					'name' => 'Who was missing',
					'type' => 'text',
					'value' => $value1,
				],
				1 => [
					'name' => 'Was it reset?',
					'type' => 'text',
					'value' => $value2,
				],
			],
			'Not Home' => [
				0 => [
					'name' => 'Did you speak it',
					'type' => 'text',
					'value' => $value1,
				],
				1 => [
					'name' => 'Was it reset?',
					'type' => 'text',
					'value' => $value2,
				],
			],
		];
 		return $fields[$key];
	}

 	private function getResultName($name)
	{
		switch ($name) {
			case 'sold':
				$name = 'Sold';
				break;
			case 'dns':
				$name = 'DNS';
				break;
			case 'one_legger':
				$name = 'One Legger';
				break;
			case 'not_home':
				$name = 'Not Home';
				break;
			
			default:
				
				break;
		}
 		return $name;
	}
}