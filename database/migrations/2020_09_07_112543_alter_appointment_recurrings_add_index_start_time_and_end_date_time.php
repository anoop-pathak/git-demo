<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentRecurringsAddIndexStartTimeAndEndDateTime extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_recurrings', function(Blueprint $table)
		{
			if (!isIndexExists('appointment_recurrings', 'appointment_recurrings_start_date_time_index')) {
				
				$table->index('start_date_time');
			}

			if (!isIndexExists('appointment_recurrings', 'appointment_recurrings_end_date_time_index')) {
				
				$table->index('end_date_time');
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
		Schema::table('appointment_recurrings', function(Blueprint $table)
		{
			$table->dropIndex(['start_date_time']);
			$table->dropIndex(['end_date_time']);
		});
	}

}
