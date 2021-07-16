<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncBatchesAddSyncActionAndSyncScopeAndSyncRequestMetaColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_batches', function(Blueprint $table) {
			$table->string('sync_action')->nullable();
			$table->string('sync_scope')->nullable();
			$table->text('sync_request_meta')->nullable();
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
			$table->dropColumn('sync_action');
			$table->dropColumn('sync_scope');
			$table->dropColumn('sync_request_meta');
		});
	}

}
