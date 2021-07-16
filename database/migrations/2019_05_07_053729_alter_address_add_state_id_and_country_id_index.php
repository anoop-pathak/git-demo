<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddressAddStateIdAndCountryIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('addresses', function(Blueprint $table) {
			if (!isIndexExists('addresses', 'addresses_state_id_index')) {
				$table->index('state_id');
			}
			if (!isIndexExists('addresses', 'addresses_country_id_index')) {
				$table->index('country_id');
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
		Schema::table('addresses', function(Blueprint $table) {
			$table->dropIndex('addresses_state_id_index');
			$table->dropIndex('addresses_country_id_index');
		});
	}

}
