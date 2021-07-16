<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobFinancialCalculationsModifyTotalInvoiceReceivedPaymentColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE job_financial_calculations MODIFY total_invoice_received_payment DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE job_financial_calculations MODIFY total_invoice_received_payment DECIMAL(8,2)");
	}

}
