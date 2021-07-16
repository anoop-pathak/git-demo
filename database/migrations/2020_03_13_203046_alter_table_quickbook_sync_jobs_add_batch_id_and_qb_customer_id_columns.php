<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncJobsAddBatchIdAndQbCustomerIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_jobs', function(Blueprint $table) {
			$table->integer('batch_id')->after('company_id')->nullable();
			$table->integer('qb_customer_id')->after('batch_id')->nullable();
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
			$table->dropColumn('batch_id');
			$table->dropColumn('qb_customer_id');
		});
	}

}
