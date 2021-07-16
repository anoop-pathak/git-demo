<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddTypeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->string('type')->nullable();
		});

		DB::statement("UPDATE job_invoices SET job_invoices.type = 'job'");
		
		DB::statement("UPDATE job_invoices
			JOIN change_orders
			ON job_invoices.id = change_orders.invoice_id
			SET job_invoices.type = 'change_order'"
		);
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
			$table->dropColumn('type');
		});
	}

}
