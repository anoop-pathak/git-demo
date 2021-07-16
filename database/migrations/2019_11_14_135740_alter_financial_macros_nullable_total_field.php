<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialMacrosNullableTotalField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE financial_macros MODIFY COLUMN fixed_price DECIMAL(16,2) NULL');
		DB::statement('ALTER TABLE worksheets MODIFY COLUMN fixed_price DECIMAL(16,2) NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE financial_macros MODIFY COLUMN fixed_price DECIMAL(16,2) NOT NULL');
		DB::statement('ALTER TABLE financial_macros MODIFY COLUMN fixed_price DECIMAL(16,2) NOT NULL');
	}

}
