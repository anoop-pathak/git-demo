<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesLastUpdatedOriginAndQuickbookSyncStatusColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->tinyInteger('quickbook_sync_status')->nullable()->default(null);

			$table->tinyInteger('last_updated_origin')->nullable()->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->dropColumn('quickbook_sync_status');

			$table->dropColumn('last_updated_origin');
		});
	}

}
