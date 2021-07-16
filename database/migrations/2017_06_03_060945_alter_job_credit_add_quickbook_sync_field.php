<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCreditAddQuickbookSyncField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_credits', function(Blueprint $table)
		{
			$table->boolean('quickbook_sync')->default(false);
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
			$table->dropColumn('quickbook_sync');
		});
	}

}
