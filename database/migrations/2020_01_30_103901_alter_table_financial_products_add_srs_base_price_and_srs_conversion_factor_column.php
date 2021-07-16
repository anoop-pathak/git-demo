<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialProductsAddSrsBasePriceAndSrsConversionFactorColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->float('srs_base_price', 16, 2)->nullable();
			$table->string('srs_conversion_factor')->nullable();
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
			$table->dropColumn('srs_base_price');
			$table->dropColumn('srs_conversion_factor');
		});
	}

}
