<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddBranchCodeAndShipToSequenceNumberColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function(Blueprint $table) {
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
		Schema::table('change_orders', function(Blueprint $table) {
			$table->dropColumn('branch_code');
			$table->dropColumn('ship_to_sequence_number');
		});
	}

}
