<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterScheduleRecurringAddSchduleIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('schedule_recurrings', function($table)
		{
			if (!isIndexExists('schedule_recurrings','schedule_recurrings_schedule_id_index')) {
				
				$table->index('schedule_id');
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
		Schema::table('schedule_recurrings', function($table){
			$table->dropIndex('schedule_recurrings_schedule_id_index');
		});
	}

}
