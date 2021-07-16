<?php

use Illuminate\Database\Seeder;
use App\Models\WaterproofingLevelType;

class WaterproofingLevelTypesTableSeeder extends Seeder {
	public function run()
	{
        WaterproofingLevelType::truncate();

		$types = [
			[
				'name' 	=> 'good',
				'type' 	=> 'waterproofing',
			],
			[
				'name' 	=> 'better',
				'type' 	=> 'waterproofing',
			],
			[
				'name' 	=> 'best',
				'type' 	=> 'waterproofing',
			],
			[
				'name' 	=> 'good',
				'type' 	=> 'levels',
			],
			[
				'name' 	=> 'better',
				'type' 	=> 'levels',
			],
			[
				'name' 	=> 'best',
				'type' 	=> 'levels',
			]
		];

        foreach ($types as $key => $type) {
			WaterproofingLevelType::create( $type );
		}
	}
}