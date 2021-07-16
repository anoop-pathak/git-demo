<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddTaxableFieldAddTaxRateField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->string('tax_rate')->nullable();
			$table->boolean('taxable')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->dropColumn('tax_rate');
			$table->dropColumn('taxable');
		});
	}

}
