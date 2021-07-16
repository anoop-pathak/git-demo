<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionsAddLastTransactionFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('subscriptions', function($table) {
			$table->dateTime('last_transaction_date')->nullable();
			$table->string('last_transaction_status')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('subscriptions', function($table) {
			$table->dropColumn('last_transaction_date');
			$table->dropColumn('last_transaction_status');
		});
	}

}
