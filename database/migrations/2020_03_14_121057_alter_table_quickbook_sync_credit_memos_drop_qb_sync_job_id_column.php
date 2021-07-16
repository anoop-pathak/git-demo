<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCreditMemosDropQbSyncJobIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_credit_memos', function(Blueprint $table) {
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
		Schema::table('quickbook_sync_credit_memos', function(Blueprint $table) {
			$table->integer('qb_sync_job_id')->nullable();
		});
	}

}
