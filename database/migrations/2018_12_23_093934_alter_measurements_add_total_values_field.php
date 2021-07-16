<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMeasurementsAddTotalValuesField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurements', function(Blueprint $table)
		{
			$table->integer('total_values')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurements', function(Blueprint $table)
		{
			$table->dropColumn('total_values');
		});
	}

}
