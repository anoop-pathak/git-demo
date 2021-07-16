<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentRecurringAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('appointment_recurrings', function($table)
		{
			if (!isIndexExists('appointment_recurrings', 'appointment_recurrings_appointment_id_index')) {
				
				$table->index('appointment_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_recurrings', function($table)
		{
			$table->dropIndex('appointment_recurrings_appointment_id_index');
		});
	}

}
