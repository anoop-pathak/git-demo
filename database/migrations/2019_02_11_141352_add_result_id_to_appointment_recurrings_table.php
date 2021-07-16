<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddResultIdToAppointmentRecurringsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_recurrings', function(Blueprint $table)
		{
			$table->integer('result_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_recurrings', function(Blueprint $table)
		{
			$table->dropColumn('result_id');
		});
	}

}
