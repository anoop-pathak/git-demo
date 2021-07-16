<?php

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodsTableSeeder extends Seeder
{

	public function run()
	{

		PaymentMethod::truncate();

		$flags = [
			[
				'id'	 =>	1,
				'label'  => 'Cash',
				'method' =>  'cash'
			],
			[
				'id'	 =>	2,
				'label'  => 'Check',
				'method' =>  'echeque'
			],
			[
				'id'	 =>	3,
				'label'  => 'Credit Card',
				'method' =>  'cc'
			],
			[
				'id'	 =>	4,
				'label'  => 'Paypal',
				'method' =>  'paypal'
			],

			[
				'id'	 =>	5,
				'label'  => 'Other',
				'method' =>  'other'
			],
			[
				'id'	=> 6,
				'label'	=> "Venmo",
				'method'=> 'venmo'
			],
			[
				'id'	=>	7,
				'label'	=> "Zelle",
				'method'=> 'zelle'
			],
			[
				'id'	=> 	8,
				'label'	=> "Digital Cash App",
				'method'=> 'Digital Cash App'
			],
			[
				'id'	=> 	9,
				'label'	=> "ACH/Online Payment",
				'method'=> 'ACH/Online Payment'
			],
		];

		PaymentMethod::insert($flags);
	}
}

?>