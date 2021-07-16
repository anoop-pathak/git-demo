<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationsListsAddMeasurementIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->integer('measurement_id')->nullable()->unsigned();
			$table->foreign('measurement_id')->references('id')->on('measurements');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->dropColumn('measurement_id');
		});
	}

}
