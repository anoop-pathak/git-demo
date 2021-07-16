<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSrsOrdersAddUniqueConstraintOnMaterialListIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE srs_orders MODIFY COLUMN material_list_id VARCHAR(255)");

		Artisan::call('command:duplicate_srs_orders_append_id_to_material_list_id');

		DB::statement("ALTER TABLE srs_orders ADD CONSTRAINT material_list_id_index UNIQUE (material_list_id)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE srs_orders DROP INDEX material_list_id_index");
	}

}
