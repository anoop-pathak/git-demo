<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkflowHistoryAddIndexOnJobId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_workflow_history', function($table)
		{
			if (!isIndexExists('job_workflow_history', 'job_workflow_history_job_id_index')) {
				
				$table->index('job_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_workflow_history', function($table)
		{
			$table->dropIndex('job_workflow_history_job_id_index');
		});
	}

}
