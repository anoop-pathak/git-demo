<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSrsOrdersAddMaterialListIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('srs_orders', function(Blueprint $table) {
			if (!isIndexExists('srs_orders', 'srs_orders_material_list_id_index')) {
				$table->index('material_list_id');
			}
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
			$table->dropIndex('srs_orders_material_list_id_index');
		});
	}

}
