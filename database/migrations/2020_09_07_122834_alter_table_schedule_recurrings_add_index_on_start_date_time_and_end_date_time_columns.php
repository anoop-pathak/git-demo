<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableScheduleRecurringsAddIndexOnStartDateTimeAndEndDateTimeColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('schedule_recurrings', function(Blueprint $table) {
			$table->index('start_date_time');
			$table->index('end_date_time');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('schedule_recurrings', function(Blueprint $table) {
			$table->dropIndex(['end_date_time']);
			$table->dropIndex(['start_date_time']);
		});
	}

}
