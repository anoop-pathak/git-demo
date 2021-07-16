<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersAddBatchIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->integer('batch_id')->after('company_id')->index();
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
			$table->dropColumn('batch_id');
		});
	}

}
