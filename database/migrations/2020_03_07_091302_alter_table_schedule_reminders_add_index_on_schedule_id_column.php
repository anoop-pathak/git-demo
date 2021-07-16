<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableScheduleRemindersAddIndexOnScheduleIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('schedule_reminders', function(Blueprint $table) {
			$table->index('schedule_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('schedule_reminders', function(Blueprint $table) {
			$table->dropIndex('schedule_reminders_schedule_id_index');
		});
	}

}
