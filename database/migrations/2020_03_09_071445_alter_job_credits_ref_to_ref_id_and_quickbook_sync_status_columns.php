<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCreditsRefToRefIdAndQuickbookSyncStatusColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_credits', function(Blueprint $table)
		{
			$table->integer('ref_id')->nullable();
			$table->integer('ref_to')->nullable();
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
		Schema::table('job_credits', function(Blueprint $table)
		{
			$table->dropColumn('ref_id');
			$table->dropColumn('ref_to');
			$table->integer('quickbook_sync_status')->nullable();
		});
	}

}
