<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookSyncCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table)
		{
			$table->integer('qb_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('sync_request_id')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table)
		{
			$table->dropColumn('qb_id');
			$table->dropColumn('customer_id');
			$table->dropColumn('sync_request_id');
		});
	}

}
