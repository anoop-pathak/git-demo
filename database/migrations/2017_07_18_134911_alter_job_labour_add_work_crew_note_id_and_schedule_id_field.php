<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobLabourAddWorkCrewNoteIdAndScheduleIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_labour', function(Blueprint $table)
		{
			$table->integer('work_crew_note_id')->unsigned()->index();
			$table->integer('schedule_id')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_labour', function(Blueprint $table)
		{
			$table->dropColumn('work_crew_note_id');
			$table->dropColumn('schedule_id');
		});
	}

}
