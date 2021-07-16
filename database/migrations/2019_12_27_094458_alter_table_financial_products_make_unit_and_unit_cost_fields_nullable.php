<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialProductsMakeUnitAndUnitCostFieldsNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `financial_products` 
			MODIFY COLUMN `unit` VARCHAR(255) NULL, 
			MODIFY COLUMN `unit_cost` FLOAT(16,2) NULL'
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `financial_products` 
			MODIFY COLUMN `unit` VARCHAR(255) NOT NULL, 
			MODIFY COLUMN `unit_cost` FLOAT(8,2) NOT NULL'
		);
	}

}
