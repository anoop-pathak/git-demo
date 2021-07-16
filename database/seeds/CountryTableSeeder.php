<?php
use Illuminate\Database\Seeder;
use App\Models\Country;

class CountryTableSeeder extends Seeder
{

    public function run()
    {
        // Country::truncate();
        $countries = [
            [
                'id'   =>  1,
                'name' => 'United States',
                'code' => 'US',
                'currency_name' => 'Doller',
				'currency_symbol' => '$'
            ],
            [
                'id'   =>  2,
                'name' => 'Bahamas',
                'code' => 'BHS',
                'currency_name' => 'Doller',
				'currency_symbol' => '$'
            ],
            [
                'id'   =>  3,
                'name' => 'Australia',
                'code' => 'AU',
                'currency_name'   => 'Doller',
				'currency_symbol' => '$'
            ],
            [
                'id'   =>  4,
                'name' => 'Canada',
                'code' => 'CA',
                'currency_name'   => 'Doller',
				'currency_symbol' => '$'
            ],
            [
                'id'   =>  5,
                'name' => 'United Kingdom',
                'code' => 'UK',
                'currency_name'   => 'Doller',
				'currency_symbol' => '$'
            ],
            [
                'id'   =>  6,
                'name' => 'Puerto Rico',
                'code' => 'PR',
                'currency_name'   => 'Doller',
				'currency_symbol' => '$'
            ],
            [
				'id'   =>  7,
				'name' => 'Europe',
				'code' => 'EUR',
				'currency_name'   => 'Euro',
				'currency_symbol' => '$'
			]
        ];

        foreach ($countries as $key => $value) {
			$country = Country::firstOrNew([
				'name' => $value['name'],
				'code' => $value['code']
			]);

			$country->currency_name = $value['currency_name'];
			$country->currency_symbol = $value['currency_symbol'];
			$country->save();
		}
    }
}
