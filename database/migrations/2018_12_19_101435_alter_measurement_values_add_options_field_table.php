<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementValuesAddOptionsFieldTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurement_formulas', function(Blueprint $table)
		{
			$table->text('options')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurement_formulas', function(Blueprint $table)
		{
			$table->dropColumn('options');
		});
	}

}
