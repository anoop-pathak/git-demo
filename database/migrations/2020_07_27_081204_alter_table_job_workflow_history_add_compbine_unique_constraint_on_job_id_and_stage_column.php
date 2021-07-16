<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobWorkflowHistoryAddCompbineUniqueConstraintOnJobIdAndStageColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Artisan::call('command:delete_duplicate_entries_from_wfh');

		Schema::table('job_workflow_history', function($table) {
			$table->unique(['job_id', 'stage']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_workflow_history', function($table) {
			$table->dropUnique('job_workflow_history_job_id_stage_unique');
		});
	}

}
