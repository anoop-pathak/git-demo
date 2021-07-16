<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookSyncTasksTableObjectLastUpdatedColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table)
		{
			$table->dateTime('object_last_updated')->after('last_modified_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table)
		{
			$table->dropColumn('object_last_updated');
		});
	}

}
