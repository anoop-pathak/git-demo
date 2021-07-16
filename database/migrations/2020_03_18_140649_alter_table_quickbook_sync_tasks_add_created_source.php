<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncTasksAddCreatedSource extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table)
		{
			$table->string('created_source')->nullable()->after('created_by');

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
			$table->dropColumn('created_source');
		});
	}

}
