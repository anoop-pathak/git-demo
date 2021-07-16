<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTasksAddJobIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function($table) {
			$table->integer('job_id')->nullable();
			$table->integer('customer_id')->nullable();
			$table->string('stage_code')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tasks', function($table) {
			$table->dropColumn('job_id');
			$table->dropColumn('customer_id');
			$table->dropColumn('stage_code');
		});
	}

}
