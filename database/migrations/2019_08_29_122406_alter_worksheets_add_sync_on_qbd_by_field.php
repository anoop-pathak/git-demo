<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddSyncOnQbdByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->integer('sync_on_qbd_by');
			$table->dropColumn('sync_on_qbd');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->dropColumn('sync_on_qbd_by');
		});
	}

}
