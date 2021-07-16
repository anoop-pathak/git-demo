<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookDeletedInvoicesAddInvoiceIdAndQbInvoiceId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('deleted_quickbook_invoices', function(Blueprint $table) {
			$table->integer('qb_invoice_id')->nullable();
			$table->integer('invoice_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('deleted_quickbook_invoices', function(Blueprint $table) {
			$table->dropColumn('qb_invoice_id');
			$table->dropColumn('invoice_id');
		});
	}

}
