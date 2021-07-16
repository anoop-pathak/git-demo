<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddInvoiceNumberField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->string('invoice_number')->index()->after('job_id');
		});

		DB::statement("UPDATE job_invoices set job_invoices.invoice_number = job_invoices.id");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->dropColumn('invoice_number');
		});
	}

}
