<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementFormulasAddActiveColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurement_formulas', function(Blueprint $table) {
			$table->boolean('active')->default(true);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurement_formulas', function(Blueprint $table) {
			$table->dropColumn('active');
		});
	}

}
