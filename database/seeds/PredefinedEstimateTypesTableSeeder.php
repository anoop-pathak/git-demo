<?php

use Illuminate\Database\Seeder;
use App\Models\PredefinedEstimateType;

class PredefinedEstimateTypesTableSeeder extends Seeder {
	public function run()
	{
        PredefinedEstimateType::truncate();

		$types = [
			[
				'name' 	=> '1',
				'type' 	=> 'layers',
			],
			[
				'name' 	=> '2',
				'type' 	=> 'layers',
			],
			[
				'name' 	=> '3',
				'type' 	=> 'layers',
			],
			[
				'name' 	=> 'dormers',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'box',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'exhaust vent',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'plumbing',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'satellite dishes',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'fans',
				'type' 	=> 'ventilation',
			],
			[
				'name' 	=> 'one level',
				'type' 	=> 'structure',
				'icon' 	=> 'clickthru/structures/one_level.svg',
			],
			[
				'name' 	=> 'multi level',
				'type' 	=> 'structure',
				'icon' 	=> 'clickthru/structures/multi_level.svg',
			],
			[
				'name' 	=> 'none of these',
				'type' 	=> 'structure',
				'icon' 	=> 'clickthru/structures/none_of_these.svg',
			],
			[
				'name' 	=> '2',
				'type' 	=> 'complexity',
				'icon' 	=> 'clickthru/structures/2_facets.svg',
			],
			[
				'name' 	=> '3-5',
				'type' 	=> 'complexity',
				'icon' 	=> 'clickthru/structures/3_5_facets.svg',
			],
			[
				'name' 	=> '5+',
				'type' 	=> 'complexity',
				'icon' 	=> 'clickthru/structures/5+_facets.svg',
			],
        ];

		foreach ($types as $key => $type) {
			PredefinedEstimateType::create( $type );
		}
	}
}