<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvOrdersAddMetaColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_orders', function(Blueprint $table) {
			$table->mediumText('meta')->nullable();
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
			$table->dropColumn('meta');
		});
	}

}
