<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncTasksAddQueueDataRelatedColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table) {
			$table->integer('queue_attempts')->nullable();
			$table->dateTime('queue_started_at')->nullable();
			$table->dateTime('queue_completed_at')->nullable();
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
			$table->dropColumn('queue_attempts');
			$table->dropColumn('queue_started_at');
			$table->dropColumn('queue_completed_at');
		});
	}

}
