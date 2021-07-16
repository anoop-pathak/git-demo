<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIsHighPriorityTaskFieldsToWorkflowTaskLists extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table)
		{
			$table->boolean('is_high_priority_task')->after('notes')->nullable();		
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
			$table->dropColumn('is_high_priority_task');
		});
	}

}
