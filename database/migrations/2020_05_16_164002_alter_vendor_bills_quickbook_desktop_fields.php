<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterVendorBillsQuickbookDesktopFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->string('qb_desktop_txn_id')->nullable();
			$table->boolean('qb_desktop_delete')->default(false);
			$table->string('qb_desktop_sequence_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vendor_bills', function(Blueprint $table)
		{
			$table->dropColumn('qb_desktop_txn_id');
			$table->dropColumn('qb_desktop_delete');
			$table->dropColumn('qb_desktop_sequence_number');
		});
	}
}
