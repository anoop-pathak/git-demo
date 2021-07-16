<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddMeasurementIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table)
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
		Schema::table('material_lists', function(Blueprint $table)
		{
			$table->dropColumn('measurement_id');
		});
	}

}
