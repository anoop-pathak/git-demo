<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersAddIgnoredColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->boolean('ignored')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->dropColumn('ignored');
		});
	}

}
