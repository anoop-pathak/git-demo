<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddCanceledFieldCanceledByFieldAddModifiedByFieldAddCreatedByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->integer('created_by');
			$table->integer('modified_by');
			$table->integer('order');
			$table->timestamp('canceled')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_orders', function(Blueprint $table)
		{
			$table->dropColumn('order');
			$table->dropColumn('created_by');
			$table->dropColumn('modified_by');
			$table->dropColumn('canceled');
		});
	}

}
