<?php

use Illuminate\Database\Seeder;
use App\Models\EstimateType;

class EstimateTypesTableSeeder extends Seeder {
	public function run()
	{
		EstimateType::truncate();
		$types = [
			[
				'name' 		=> 'Roof Over',
			],
			[
				'name' 	=> 'Tear Off',
			]
		];
		foreach ($types as $key => $type) {
			EstimateType::create( $type );
		}
	}
}