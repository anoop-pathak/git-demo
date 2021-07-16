<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrderEntitiesAddQuantityField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_order_entities', function(Blueprint $table)
		{
			$table->string('quantity', 7)->default(1);
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
			$table->dropColumn('quantity');
		});
	}

}
