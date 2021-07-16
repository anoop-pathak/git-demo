<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentRecurringsRemoveResultIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_recurrings', function(Blueprint $table) {
			$table->dropColumn('result_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_recurrings', function(Blueprint $table) {
			$table->integer('result_id')->default(0);
		});
	}

}
