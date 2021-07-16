<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoiceLinesAddQuickbookDesktopFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table)
		{
			$table->string('qb_txn_line_id')->nullable();
			$table->string('qb_item_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoice_lines', function(Blueprint $table)
		{
			$table->dropColumn('qb_txn_line_id');
			$table->dropColumn('qb_item_id');
		});
	}

}
