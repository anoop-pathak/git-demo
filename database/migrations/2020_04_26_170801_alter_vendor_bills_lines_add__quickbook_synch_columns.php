<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterVendorBillsLinesAddQuickbookSynchColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendor_bill_lines', function(Blueprint $table) {
			$table->integer('quickbook_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vendor_bill_lines', function(Blueprint $table) {
			$table->dropColumn('quickbook_id');
		});
	}

}
