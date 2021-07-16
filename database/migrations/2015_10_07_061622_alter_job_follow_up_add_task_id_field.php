<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFollowUpAddTaskIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_follow_up', function($table){
			$table->dropColumn('appointment_id');
		});
		Schema::table('job_follow_up', function($table){
			$table->integer('task_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_follow_up', function($table){
			$table->dropColumn('task_id');
		});
		Schema::table('job_follow_up', function($table){
			$table->integer('appointment_id')->nullable();
		});
	}

}
