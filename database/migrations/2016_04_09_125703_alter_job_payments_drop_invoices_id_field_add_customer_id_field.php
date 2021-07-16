<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsDropInvoicesIdFieldAddCustomerIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->dropColumn('invoice_id');
			$table->integer('customer_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->integer('invoice_id')->nullable();
			$table->dropColumn('customer_id');
		});
	}

}
