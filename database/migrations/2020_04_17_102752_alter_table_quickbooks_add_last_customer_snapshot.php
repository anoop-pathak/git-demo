<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbooksAddLastCustomerSnapshot extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks', function(Blueprint $table) {
			$table->dateTime('last_customer_snapshot')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks', function(Blueprint $table) {
			$table->dropColumn('last_customer_snapshot');
		});
	}

}
