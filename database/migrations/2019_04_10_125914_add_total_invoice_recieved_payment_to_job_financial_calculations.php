<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTotalInvoiceRecievedPaymentToJobFinancialCalculations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->decimal('total_invoice_received_payment')->nullable()->after('total_received_payemnt');
			$table->decimal('total_change_order_invoice_amount')->nullable()->after('total_change_order_amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->dropColumn('total_invoice_received_payment');
			$table->dropColumn('total_change_order_invoice_amount');
		});
	}

}
