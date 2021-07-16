<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddBranchCodeAndShipToSequenceNumberColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table) {
			$table->string('branch_code')->nullable();
			$table->integer('ship_to_sequence_number')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('worksheets', function(Blueprint $table) {
			$table->dropColumn('branch_code');
			$table->dropColumn('ship_to_sequence_number');
		});
	}

}
