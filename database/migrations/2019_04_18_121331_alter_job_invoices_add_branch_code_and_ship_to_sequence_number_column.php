<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddBranchCodeAndShipToSequenceNumberColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function(Blueprint $table) {
			$table->string('branch_code')->nullable();
			$table->string('ship_to_sequence_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function(Blueprint $table) {
			$table->dropColumn('branch_code');
			$table->dropColumn('ship_to_sequence_number');
		});
	}

}
