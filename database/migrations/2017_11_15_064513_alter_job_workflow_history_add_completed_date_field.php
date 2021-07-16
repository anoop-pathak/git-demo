<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkflowHistoryAddCompletedDateField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_workflow_history', function(Blueprint $table)
		{
			$table->dateTime('completed_date')->after('start_date');
		});

		DB::statement("UPDATE job_workflow_history 
			set job_workflow_history.completed_date = job_workflow_history.created_at ");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_workflow_history', function(Blueprint $table)
		{
			$table->dropColumn('completed_date');
		});
	}

}
