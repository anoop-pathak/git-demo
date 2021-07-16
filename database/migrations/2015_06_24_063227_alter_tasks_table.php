<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTasksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function($table){
			$table->dropColumn('user_id');
			$table->dropColumn('google_task_id');
			$table->integer('completed_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tasks', function($table){
			$table->integer('user_id');
			$table->string('google_task_id')->nullable();
			$table->dropColumn('completed_by');
		});
	}

}
