<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsAddQuickbookSyncStatusColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->tinyInteger('quickbook_sync_status')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->dropColumn('quickbook_sync_status');
		});
	}

}
