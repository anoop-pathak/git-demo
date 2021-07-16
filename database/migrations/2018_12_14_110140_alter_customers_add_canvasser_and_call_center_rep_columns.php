<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomersAddCanvasserAndCallCenterRepColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customers', function(Blueprint $table) {
			$table->string('canvasser')->nullable();
			$table->string('call_center_rep')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('customers', function(Blueprint $table) {
			$table->dropColumn('canvasser');
			$table->dropColumn('call_center_rep');
		});
	}

}
