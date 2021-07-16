<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncJobsDropQbSyncCustomerIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_jobs', function(Blueprint $table) {
			$table->dropColumn('qb_sync_customer_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_jobs', function(Blueprint $table) {
			$table->integer('qb_sync_customer_id')->nullable();
		});
	}

}
