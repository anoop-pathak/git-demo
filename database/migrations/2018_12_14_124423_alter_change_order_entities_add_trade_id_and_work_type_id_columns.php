<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrderEntitiesAddTradeIdAndWorkTypeIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_order_entities', function(Blueprint $table) {
			$table->integer('trade_id')->nullable();
			$table->integer('work_type_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_order_entities', function(Blueprint $table) {
			$table->dropColumn('trade_id');
			$table->dropColumn('work_type_id');
		});
	}

}
