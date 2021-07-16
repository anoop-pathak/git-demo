<?php
use Illuminate\Database\Seeder;
use App\Models\VendorTypes;

class VendorTypesTableSeeder extends Seeder {

	public function run()
	{
		$vendorTypes = [
			[
				'name' => 'Measurements',
				'display_order' => 1,
				'company_id' 	=> 0,
			],
			[
				'name' => 'Sub Contractors',
				'display_order'	=> 2,
				'company_id' 	=> 0,
			],
			[
				'name' => 'Suppliers',
				'display_order'	=> 3,
				'company_id' 	=> 0,
			],
			[
				'name' => 'Others',
				'display_order'	=> 4,
				'company_id'	=> 0,
			],
		];

		foreach ($vendorTypes as $data) {
			VendorTypes::firstOrCreate($data);
		}
	}

}