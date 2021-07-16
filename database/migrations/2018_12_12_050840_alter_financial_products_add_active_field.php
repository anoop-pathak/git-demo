<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddActiveField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->boolean('active')->default(true)->comment('for sub contractors');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->dropColumn('active');
		});
	}

}
