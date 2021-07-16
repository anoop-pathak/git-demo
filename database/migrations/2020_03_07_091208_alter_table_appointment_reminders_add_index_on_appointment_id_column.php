<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAppointmentRemindersAddIndexOnAppointmentIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_reminders', function(Blueprint $table) {
			$table->index('appointment_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_reminders', function(Blueprint $table) {
			$table->dropIndex('appointment_reminders_appointment_id_index');
		});
	}

}
