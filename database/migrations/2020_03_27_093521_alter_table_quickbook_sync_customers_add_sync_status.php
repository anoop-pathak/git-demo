<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersAddSyncStatus extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->string('sync_status')->nullable()->default('ready_for_synching');
			$table->string('group_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->dropColumn('sync_status');
			$table->dropColumn('group_id');
		});
	}

}
