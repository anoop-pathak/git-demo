<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterJobInvoicesIndexQuickbookInvoiceIdField extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_invoices', function(Blueprint $table) {
 			if (!isIndexExists('job_invoices', 'job_invoices_quickbook_invoice_id_index')) {
 				$table->index('quickbook_invoice_id');
 			}
 			if (!isIndexExists('job_invoices', 'job_invoices_type_index')) {
 				$table->index('type');
 			}
 			if (!isIndexExists('job_invoices', 'job_invoices_custom_tax_id_index')) {
 				$table->index('custom_tax_id');
 			}
 		});
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
			//
 		});
 	}
 }