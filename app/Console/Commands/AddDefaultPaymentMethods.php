<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\PaymentMethod;

class AddDefaultPaymentMethods extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_payment_methods';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command to add Default payment method';

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
		$this->info('Command Start Date:'. \Carbon\Carbon::now());
		Company::chunk(50, function($companies) {
				foreach ($companies as $company) {
					$this->info('Add Payment Method for:'. $company->id);
					$this->addPaymentMethods($company);
				}
			});

		PaymentMethod::where('company_id', 0)->delete();
		$this->info('Command Start Date:'. \Carbon\Carbon::now());
	}

	public function addPaymentMethods($company)
	{
		$defaultMethods = [
			[
				'label'			=> "Cash",
				'method'		=> 'cash',
			],
			[
				'label'			=> "Check",
				'method'		=> 'echeque',
			],
			[
				'label'			=> "Credit Card",
				'method'		=> 'cc',
			],
			[
				'label'			=> "Paypal",
				'method'		=> 'paypal',
			],
			[
				'label'			=> "Venmo",
				'method'		=> 'venmo',
			],
			[
				'label'			=> "Zelle",
				'method'		=> 'zelle',
			],
			[
				'label'			=> "Digital Cash App",
				'method'		=> 'Digital Cash App',
			],
			[
				'label'			=> "Other",
				'method'		=> 'other',
			],
			[
				'label'     	=> "ACH/Online Payment",
				'method'        => 'ACH/Online Payment'
			]
		];

		$data = [];
		$companyMethods = PaymentMethod::where('company_id', $company->id)->pluck('method')->toArray();

		foreach ($defaultMethods as $value) {

			if(in_array($value['method'], $companyMethods)) {
				continue;
			}
			$now = Carbon::now()->toDateTimeString();

			$value['company_id'] = $company->id;
			$value['created_at'] = $now;
			$value['updated_at'] = $now;

			$data[] = $value;
		}

		if(!empty($data)) {
			PaymentMethod::insert($data);
		}
	}

}
