<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersAddCustomerDetailFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->string('first_name');
			$table->string('last_name');
			$table->string('email')->nullable();
			$table->text('additional_emails')->nullable();
			$table->text('phones')->nullable();
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
			$table->dropColumn('first_name');
			$table->dropColumn('last_name');
			$table->dropColumn('email');
			$table->dropColumn('additional_emails');
			$table->dropColumn('phones');
		});
	}

}
