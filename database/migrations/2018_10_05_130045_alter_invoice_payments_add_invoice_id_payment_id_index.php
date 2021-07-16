<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterInvoicePaymentsAddInvoiceIdPaymentIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('invoice_payments', function(Blueprint $table)
		{
			$table->index('payment_id');
			$table->index('invoice_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('invoice_payments', function(Blueprint $table)
		{
			$table->dropIndex('invoice_payments_payment_id_index');
			$table->dropIndex('invoice_payments_invoice_id_index');
		});
	}

}
