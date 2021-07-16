<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBackupFinancialMacrosAndFinancialProductsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// $date = date('YmdHis');
		// DB::statement("CREATE TABLE financial_macros_back_up_{$date} LIKE financial_macros");
		// DB::statement("CREATE TABLE financial_products_back_up_{$date} LIKE financial_products");
		// DB::statement("INSERT INTO financial_macros_back_up_{$date} SELECT * FROM financial_macros");
		// DB::statement("INSERT INTO financial_products_back_up_{$date} SELECT * FROM financial_products");
		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
