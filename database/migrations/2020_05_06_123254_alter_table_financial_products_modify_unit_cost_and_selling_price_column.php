<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialProductsModifyUnitCostAndSellingPriceColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE financial_products MODIFY unit_cost DECIMAL(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE financial_products MODIFY selling_price DECIMAL(16,2) DEFAULT Null");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE financial_products MODIFY unit_cost Float(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE financial_products MODIFY selling_price Float(8,2) DEFAULT Null");
	}

}
