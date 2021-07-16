<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCreditMemosAddBatchIdAndQbSyncJobIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_credit_memos', function(Blueprint $table) {
			$table->integer('batch_id')->after('quickbook_customer_id')->nullable();
			$table->integer('qb_sync_job_id')->after('batch_id')->nullable()->comment("id of quickbook_sync_jobs table");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_credit_memos', function(Blueprint $table) {
			$table->dropColumn('batch_id');
			$table->dropColumn('qb_sync_job_id');
		});
	}

}
