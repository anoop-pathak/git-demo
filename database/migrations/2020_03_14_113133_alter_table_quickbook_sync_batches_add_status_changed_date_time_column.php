<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncBatchesAddStatusChangedDateTimeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_batches', function(Blueprint $table) {
			$table->dateTime('status_changed_date_time')->nullable()->after('status');
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
			$table->dropColumn('status_changed_date_time');
		});
	}

}
