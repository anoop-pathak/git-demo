<?php
use Illuminate\Database\Seeder;
use App\Models\SubscriberStageAttribute;

class SubscriberStageAttributesTableSeeder extends Seeder
{

	public function run()
	{
		$subscriberStageAttributes = [
			[
				'name' => 'Blank',
				'color_code' => '#ffffff',
			],
			[
				'name' => 'Yellow',
				'color_code' => '#F7F700',
			],
			[
				'name' => 'Green',
				'color_code' => '#97F784',
			],
			[
				'name' => 'Green T1',
				'color_code' => '#37F713',
			],
			[
				'name' => 'Green T2',
				'color_code' => '#74B81F',
			],
			[
				'name' => 'Green T3',
				'color_code' => '#218721',
			],
			[
				'name' => 'Red',
				'color_code' => '#ED0000',
			],
		];

		foreach ($subscriberStageAttributes as $key => $stageAttributes) {
			SubscriberStageAttribute::firstOrCreate( $stageAttributes );
		}
	}
}