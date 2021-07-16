<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRefIdToInvoicePayments extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('invoice_payments', function(Blueprint $table)
		{
			$table->integer('ref_id')->nullable();
			$table->integer('job_id')->nullable()->after('payment_id');
		});
		DB::statement("UPDATE invoice_payments
			JOIN job_invoices
			ON invoice_payments.invoice_id = job_invoices.id
			SET invoice_payments.job_id = job_invoices.job_id"
		);

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
			$table->dropColumn('ref_id');
			$table->dropColumn('job_id');
		});
	}

}
