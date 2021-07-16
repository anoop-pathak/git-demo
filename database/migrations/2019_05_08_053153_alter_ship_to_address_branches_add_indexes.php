<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterShipToAddressBranchesAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ship_to_address_branches', function(Blueprint $table) {
			if (!isIndexExists('ship_to_address_branches', 'ship_to_address_branches_supplier_branch_id_index')) {
				$table->index('supplier_branch_id');
			}
			if (!isIndexExists('ship_to_address_branches', 'ship_to_address_branches_srs_ship_to_address_id_index')) {
				$table->index('srs_ship_to_address_id');
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
		Schema::table('ship_to_address_branches', function(Blueprint $table) {
			$table->dropIndex('ship_to_address_branches_supplier_branch_id_index');
			$table->dropIndex('ship_to_address_branches_srs_ship_to_address_id_index');
		});
	}

}
