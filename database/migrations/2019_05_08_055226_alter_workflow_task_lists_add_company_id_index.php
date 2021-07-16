<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorkflowTaskListsAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_task_lists', function(Blueprint $table) {
			if (!isIndexExists('workflow_task_lists', 'workflow_task_lists_company_id_index')) {
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
		Schema::table('workflow_task_lists', function(Blueprint $table) {
			$table->dropIndex('workflow_task_lists_company_id_index');
		});
	}

}
