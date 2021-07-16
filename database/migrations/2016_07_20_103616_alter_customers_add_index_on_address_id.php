<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomersAddIndexOnAddressId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customers', function($table)
		{
			if (!isIndexExists('customers', 'customers_address_id_index')) {
				
				$table->index('address_id');
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
		Schema::table('customers', function($table)
		{
			$table->dropIndex('customers_address_id_index');
		});
	}

}
