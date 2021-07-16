<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSrsOrdersAddSrsOrderIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('srs_orders', function(Blueprint $table) {
			$table->string('srs_order_id')->nullable()->after('order_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('srs_orders', function(Blueprint $table) {
			$table->dropColumn('srs_order_id');
		});
	}

}
