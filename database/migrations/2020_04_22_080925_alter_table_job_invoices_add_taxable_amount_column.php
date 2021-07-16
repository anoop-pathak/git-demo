<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobInvoicesAddTaxableAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if(!Schema::hasColumn('job_invoices', 'taxable_amount')) {
			Schema::table('job_invoices', function(Blueprint $table) {
				$table->decimal('taxable_amount', 16, 2)->nullable();
			});
		}

		// update taxable amount of invoices created from JP origin
		DB::statement("
			UPDATE `job_invoices`
			SET taxable_amount = IF(job_invoices.taxable = 1, CAST(job_invoices.total_amount AS decimal(16, 2)), 0)
			WHERE job_invoices.taxable=1
			AND origin = 0
		");

		// update taxable amount of invoices created from QB origin
		DB::statement("
			UPDATE job_invoices invoices
			INNER JOIN
				(
					SELECT invoice_id,
					sum(IF(is_taxable = 1, amount*quantity, 0)) AS tax_amount
					FROM job_invoice_lines
					GROUP BY invoice_id
				) invoice_lines ON invoice_lines.invoice_id = invoices.id
			SET invoices.taxable_amount = invoice_lines.tax_amount
			WHERE invoices.taxable=1
			AND origin = 1;
		");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function(Blueprint $table) {
			$table->dropColumn('taxable_amount');
		});
	}

}
