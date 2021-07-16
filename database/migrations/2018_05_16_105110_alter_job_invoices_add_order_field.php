<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddOrderField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->integer('order')->default(1);
		});

		DB::statement('UPDATE job_invoices SET job_invoices.order = 1 WHERE type = "job"');
		DB::statement('UPDATE job_invoices INNER JOIN change_orders ON job_invoices.id = change_orders.invoice_id SET job_invoices.order = change_orders.order');

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
			$table->dropColumn('order');
		});
	}

}
