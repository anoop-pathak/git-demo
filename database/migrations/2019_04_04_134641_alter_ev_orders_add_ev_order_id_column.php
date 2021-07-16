<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvOrdersAddEvOrderIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_orders', function(Blueprint $table) {
			$table->string('ev_order_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ev_orders', function(Blueprint $table) {
			$table->dropColumn('ev_order_id');
		});
	}

}
