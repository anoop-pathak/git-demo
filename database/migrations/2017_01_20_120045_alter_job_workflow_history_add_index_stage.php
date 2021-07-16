<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkflowHistoryAddIndexStage extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_workflow_history', function($table) 
		{
			if (!isIndexExists('job_workflow_history', 'job_workflow_history_stage_index')) {
				
				$table->index('stage');
			}
			
			if (!isIndexExists('job_workflow_history', 'job_workflow_history_company_id_index')) {
				
				$table->index('company_id');
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
		Schema::table('job_workflow_history', function($table) {
			$table->dropIndex('job_workflow_history_stage_index');
			$table->dropIndex('job_workflow_history_company_id_index');
		});
	}

}
