<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialDetailsModifyQuantitySellingPriceAndUnitCostColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE `financial_details` MODIFY `quantity` DECIMAL(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE `financial_details` MODIFY COLUMN `selling_price` DECIMAL(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE `financial_details` MODIFY COLUMN `unit_cost` DECIMAL(16,2) DEFAULT Null");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE `financial_details` MODIFY COLUMN `quantity` Float(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE `financial_details` MODIFY COLUMN `selling_price` Float(16,2) DEFAULT Null");
		DB::statement("ALTER TABLE `financial_details` MODIFY COLUMN `unit_cost` Float(16,2) DEFAULT Null");
	}

}
