<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncInvoicesDropQbSyncJobIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_invoices', function(Blueprint $table) {
			$table->dropColumn('qb_sync_job_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_invoices', function(Blueprint $table) {
			$table->integer('qb_sync_job_id')->nullable();
		});
	}

}
