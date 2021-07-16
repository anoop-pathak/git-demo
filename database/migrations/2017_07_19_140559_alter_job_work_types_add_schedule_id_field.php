<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkTypesAddScheduleIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_work_types', function(Blueprint $table)
		{
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
		Schema::table('job_work_types', function(Blueprint $table)
		{
			$table->dropColumn('schedule_id');
		});
	}

}
