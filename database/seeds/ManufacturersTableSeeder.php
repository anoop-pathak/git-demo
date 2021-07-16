<?php

use Illuminate\Database\Seeder;
use App\Models\Manufacturer;

class ManufacturersTableSeeder extends Seeder {
	public function run()
	{
        Manufacturer::truncate();

		$manufacturers = [
			[
				'name' 		=> 'GAF',
				'logo' 		=> 'clickthru/manufacturers/gaf.png',
			],
			[
				'name' 	=> 'Certainteed',
				'logo' 	=> 'clickthru/manufacturers/certainteed.png',
			],
			[
				'name' 	=> 'Ovens Corning',
				'logo' 	=> 'clickthru/manufacturers/owens-corning.png',
			],
			[
				'name' 	=> 'IKO',
				'logo' 	=> 'clickthru/manufacturers/iko.png',
			],
        ];

		foreach ($manufacturers as $key => $manufacturer) {
			Manufacturer::create( $manufacturer );
		}
	}
}