<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialTableMakeNameColumnNullable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `financial_products` 
			MODIFY COLUMN `name` VARCHAR(255) NULL'
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
			MODIFY COLUMN `name` VARCHAR(255) NOT NULL'
		);
	}

}
