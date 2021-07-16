<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementAttributesAddActiveColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurement_attributes', function(Blueprint $table) {
			$table->boolean('active')->default(true)->after('trade_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurement_attributes', function(Blueprint $table) {
			$table->dropColumn('active');
		});
	}

}
