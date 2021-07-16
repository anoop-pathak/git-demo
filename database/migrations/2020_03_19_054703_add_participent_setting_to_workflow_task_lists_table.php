<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddParticipentSettingToWorkflowTaskListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table)
		{
			$table->string('assign_to_setting')->nullable();
			$table->string('notify_user_setting')->nullable();
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
			$table->dropColumn('assign_to_setting');
			$table->dropColumn('notify_user_setting');
		});
	}

}
