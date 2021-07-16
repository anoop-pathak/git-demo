<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddInvoiceIdAndDivisionIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function(Blueprint $table) {
			if (!isIndexExists('change_orders', 'change_orders_invoice_id_index')) {
				$table->index('invoice_id');
			}
			if (!isIndexExists('change_orders', 'change_orders_division_id_index')) {
				$table->index('division_id');
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
		Schema::table('change_orders', function(Blueprint $table) {
			$table->dropIndex('change_orders_invoice_id_index');
			$table->dropIndex('change_orders_division_id_index');
		});
	}

}
