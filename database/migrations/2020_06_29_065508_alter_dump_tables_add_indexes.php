<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDumpTablesAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbd_bills', function(Blueprint $table) {
			$table->index('company_id');
			$table->index('customer_ref');
			$table->index('vendor_ref');
			$table->index('qb_desktop_txn_id');
		});
		Schema::table('qbd_credit_memo', function(Blueprint $table) {
			$table->index('company_id');
			$table->index('customer_ref');
			$table->index('qb_desktop_txn_id');
		});
		Schema::table('qbd_invoices', function(Blueprint $table) {
			$table->index('company_id');
			$table->index('customer_ref');
			$table->index('qb_desktop_txn_id');
		});
		Schema::table('qbd_payments', function(Blueprint $table) {
			$table->index('company_id');
			$table->index('customer_ref');
			$table->index('qb_desktop_txn_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbd_bills', function(Blueprint $table) {
			$table->dropIndex('qbd_bills_company_id_index');
			$table->dropIndex('qbd_bills_customer_ref_index');
			$table->dropIndex('qbd_bills_vendor_ref_index');
			$table->dropIndex('qbd_bills_qb_desktop_txn_id_index');
		});
		Schema::table('qbd_credit_memo', function(Blueprint $table) {
			$table->dropIndex('qbd_credit_memo_company_id_index');
			$table->dropIndex('qbd_credit_memo_customer_ref_index');
			$table->dropIndex('qbd_credit_memo_qb_desktop_txn_id_index');
		});
		Schema::table('qbd_invoices', function(Blueprint $table) {
			$table->dropIndex('qbd_invoices_company_id_index');
			$table->dropIndex('qbd_invoices_customer_ref_index');
			$table->dropIndex('qbd_invoices_qb_desktop_txn_id_index');
		});
		Schema::table('qbd_payments', function(Blueprint $table) {
			$table->dropIndex('qbd_payments_company_id_index');
			$table->dropIndex('qbd_payments_customer_ref_index');
			$table->dropIndex('qbd_payments_qb_desktop_txn_id_index');
		});
	}

}
