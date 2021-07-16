<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddTotalAmountField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table)
		{
			$table->decimal('total_amount', 16, 2)->nullable();
		});

		DB::statement("UPDATE `job_invoices` SET total_amount = IF(job_invoices.taxable = 1, CAST((job_invoices.amount + ((job_invoices.amount * job_invoices.tax_rate) / 100)) AS decimal(16, 2)), job_invoices.amount)");
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
			$table->dropColumn('total_amount');
		});
	}

}
