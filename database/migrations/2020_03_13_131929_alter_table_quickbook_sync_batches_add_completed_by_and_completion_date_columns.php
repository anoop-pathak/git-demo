<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncBatchesAddCompletedByAndCompletionDateColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_batches', function(Blueprint $table) {
			$table->integer('completed_by')->index()->nullable();
			$table->dateTime('completion_date')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_batches', function(Blueprint $table) {
			$table->dropColumn('completed_by');
			$table->dropColumn('completion_date');
		});
	}

}
