<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobWorkflowAddIndexOnJobIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_workflow', function($table)
		{
			if (!isIndexExists('job_workflow','job_workflow_job_id_index')) {
				
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
		Schema::table('job_workflow', function($table)
		{
			$table->dropIndex('job_workflow_job_id_index');
		});
	}

}
