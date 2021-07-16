<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersAddUseJpFinancial extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->boolean('use_jp_financial')->default(true);
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
			$table->dropColumn('use_jp_financial');
		});
	}

}
