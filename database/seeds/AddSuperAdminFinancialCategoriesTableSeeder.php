<?php

use Illuminate\Database\Seeder;
use App\Models\FinancialCategory;

class AddSuperAdminFinancialCategoriesTableSeeder extends Seeder
{
	public function run()
	{
		$categories = [
			FinancialCategory::LABOR,
			FinancialCategory::INSURANCE,
		];

		foreach ($categories as $key => $value) {
			$category = FinancialCategory::firstOrNew([
				'name' => $value,
				'company_id' => 0,
			]);
			$category->default 	= true;
			$category->order 	= 0;
			$category->slug 	= strtolower(str_replace(' ', '_', $value));
			$category->save();
		}

		return true;
	}
}