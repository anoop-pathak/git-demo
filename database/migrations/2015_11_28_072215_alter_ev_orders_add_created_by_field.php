<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvOrdersAddCreatedByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_orders', function(Blueprint $table)
		{
			$table->integer('created_by');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ev_orders', function(Blueprint $table)
		{
			$table->dropColumn('created_by');
		});
	}

}
