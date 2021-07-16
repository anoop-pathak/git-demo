<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddStylesSizesColorsFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->text('styles')->nullable();
			$table->text('sizes')->nullable();
			$table->text('colors')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->dropColumn('styles');
			$table->dropColumn('sizes');
			$table->dropColumn('colors');
		});
	}

}
