<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableWorkflowTaskListsAddTaskReminderColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table) {
			$table->string('reminder_type')->nullable();
			$table->string('reminder_frequency')->nullable();
			$table->boolean('is_due_date_reminder')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table) {
			$table->dropColumn('reminder_type');
			$table->dropColumn('reminder_frequency');
			$table->dropColumn('is_due_date_reminder');
		});
	}

}
