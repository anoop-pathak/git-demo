<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSrsShipToAddressesAddCompanyIdAndCompanySupplierIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('srs_ship_to_addresses', function(Blueprint $table) {
			if (!isIndexExists('srs_ship_to_addresses', 'srs_ship_to_addresses_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('srs_ship_to_addresses', 'srs_ship_to_addresses_company_supplier_id_index')) {
				$table->index('company_supplier_id');
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
		Schema::table('srs_ship_to_addresses', function(Blueprint $table) {
			$table->dropIndex('srs_ship_to_addresses_company_id_index');
			$table->dropIndex('srs_ship_to_addresses_company_supplier_id_index');
		});
	}

}
