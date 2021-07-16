<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookDeletedPaymentAddPaymentIdAndQbPaymentId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('deleted_quickbook_payments', function(Blueprint $table) {
			$table->integer('qb_payment_id')->nullable();
			$table->integer('payment_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('deleted_quickbook_payments', function(Blueprint $table) {
			$table->dropColumn('qb_payment_id');
			$table->dropColumn('payment_id');
		});
	}

}
