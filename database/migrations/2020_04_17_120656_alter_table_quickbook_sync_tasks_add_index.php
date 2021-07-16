<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncTasksAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table) {
			$table->index('company_id');
			$table->index('status');
			$table->index(['object', 'object_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table) {
			$table->dropIndex('quickbook_sync_tasks_status_index');
			$table->dropIndex('quickbook_sync_tasks_company_id_index');
			$table->dropIndex('quickbook_sync_tasks_object_object_id_index');
		});
	}

}
