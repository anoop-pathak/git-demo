<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrderEntitiesAddProductIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_order_entities', function(Blueprint $table)
		{
			$table->integer('product_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_order_entities', function(Blueprint $table)
		{
			$table->dropColumn('product_id');
		});
	}

}
