<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncInvoicesAddBatchIdAndQbSyncJobIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_invoices', function(Blueprint $table) {
			$table->integer('batch_id')->after('company_id')->index()->nullable();
			$table->integer('qb_sync_job_id')->after('quickbook_customer_id')->index()->nullable()->comment('Id of quickbook_sync_jobs table');
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
			$table->dropColumn('batch_id');
			$table->dropColumn('qb_sync_job_id');
		});
	}

}
