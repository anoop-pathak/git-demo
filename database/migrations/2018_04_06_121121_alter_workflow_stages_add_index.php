<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorkflowStagesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('workflow_stages', function($table)
		{
			if (!isIndexExists('workflow_stages', 'workflow_stages_workflow_id_index')) {
				
				$table->index('workflow_id');
			}

			if (!isIndexExists('workflow_stages', 'workflow_stages_code_index')) {
				
				$table->index('code');
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
		Schema::table('workflow_stages', function($table)
		{
			$table->dropIndex('workflow_stages_workflow_id_index');
			$table->dropIndex('workflow_stages_code_index');
		});
	}

}
