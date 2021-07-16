<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFinancialCalculationsAddColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->decimal('job_invoice_amount', 16, 2)->default(0);
			$table->decimal('job_invoice_tax_amount', 16, 2)->default(0);
		});

		DB::statement("UPDATE job_financial_calculations INNER JOIN (SELECT IF(taxable = 1,((amount * tax_rate)/100), 0) AS tax_amount, amount, job_id FROM job_invoices WHERE amount != 0 AND type = 'job' AND deleted_at IS NULL) AS job_invoices ON job_invoices.job_id = job_financial_calculations.job_id  SET job_financial_calculations.job_invoice_tax_amount =  job_invoices.tax_amount, job_financial_calculations.job_invoice_amount =  job_invoices.amount");

		DB::statement("UPDATE job_financial_calculations INNER JOIN (SELECT sum(job_invoice_amount) as invoice_amount, sum(job_invoice_tax_amount) as tax_amount, sum(pl_sheet_total) as sheet_total,  sum(total_job_amount) as job_amount, sum(total_change_order_amount) as change_order_amount, sum(total_amount) as total, sum(total_received_payemnt) as received_payemnt, sum(total_credits) as credits, sum(pending_payment) as pending_pay, sum(total_commission) as total_comm, parent_id  FROM jobs 
			INNER JOIN job_financial_calculations 
			ON job_financial_calculations.job_id = jobs.id 
			WHERE jobs.parent_id IS NOT NULL
			AND jobs.deleted_at IS NULL 
			AND jobs.awarded = true
			GROUP BY jobs.parent_id) as parent_financial on parent_financial.parent_id = job_financial_calculations.job_id 
				SET job_invoice_tax_amount =  parent_financial.tax_amount,
				job_invoice_amount =  parent_financial.invoice_amount,
				pl_sheet_total = parent_financial.sheet_total,
				 total_job_amount = parent_financial.job_amount, total_change_order_amount = parent_financial.change_order_amount, total_amount = parent_financial.total, total_received_payemnt = parent_financial.received_payemnt, total_credits = parent_financial.credits, pending_payment = parent_financial.pending_pay, total_commission = parent_financial.total_comm
				WHERE multi_job_sum = 1");

		//Project invoice title changes for old project
		DB::statement("UPDATE job_invoices INNER JOIN jobs on jobs.id = job_invoices.job_id 
			SET title = REPLACE(title, 'Job Invoice', 'Project Invoice') WHERE job_invoices.type = 'job' AND jobs.parent_id IS NOT NULL");
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
			$table->dropColumn('job_invoice_amount');
			$table->dropColumn('job_invoice_tax_amount');
		});
	}

}
