<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementAttributesAddLockedField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurement_attributes', function(Blueprint $table)
		{
			$table->boolean('locked')->default(false);
		});

		DB::statement("UPDATE measurement_attributes SET locked = true WHERE trade_id = 8");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurement_attributes', function(Blueprint $table)
		{
			$table->dropColumn('locked');
		});
	}

}
