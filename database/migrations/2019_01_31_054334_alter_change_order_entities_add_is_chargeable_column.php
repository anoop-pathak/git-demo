<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrderEntitiesAddIsChargeableColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_order_entities', function(Blueprint $table) {
			$table->boolean('is_chargeable')->default(true);
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
			$table->dropColumn('is_chargeable');
		});
	}

}
