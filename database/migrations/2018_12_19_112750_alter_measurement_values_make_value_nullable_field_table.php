<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementValuesMakeValueNullableFieldTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `measurement_values` MODIFY `value` varchar(255)  NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		 DB::statement('ALTER TABLE `measurement_values` MODIFY `value` varchar(255)  NOT NULL;');
	}

}

