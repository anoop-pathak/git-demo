<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWorkflowTaskListsAddLocked extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table) {
			$table->boolean('locked')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table)
		{
			$table->dropColumn('locked')->default(0);
		});
	}

}
