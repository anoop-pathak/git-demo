<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobSchedulesAddSubjectEditedField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_schedules', function(Blueprint $table)
		{
			$table->boolean('subject_edited')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_schedules', function(Blueprint $table)
		{
			$table->dropColumn('subject_edited');
		});
	}

}
