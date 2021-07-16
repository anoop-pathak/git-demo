<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHoverJobIdToMeasurementsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurements', function(Blueprint $table)
		{
			$table->integer('hover_job_id')->nullable();
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
			$table->dropColumn('hover_job_id');
		});
	}

}
