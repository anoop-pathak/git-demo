<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddLastUpdatedOriginAndQuickbookSyncStatusColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			$table->tinyInteger('last_updated_origin')->nullable();

			$table->tinyInteger('quickbook_sync_status')->nullable()->default(null);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			$table->dropColumn('last_updated_origin');

			$table->dropColumn('quickbook_sync_status');
		});
	}

}
