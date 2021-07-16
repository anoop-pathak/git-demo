<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddQuickbookInvoiceIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->string('quickbook_invoice_id')->nullable();
			$table->integer('quickbook_sync_token')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->dropColumn('quickbook_invoice_id');
			$table->dropColumn('quickbook_sync_token');
		});
	}

}
