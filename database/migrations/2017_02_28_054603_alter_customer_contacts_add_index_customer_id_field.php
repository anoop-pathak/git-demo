<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomerContactsAddIndexCustomerIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customer_contacts', function(Blueprint $table)
		{
			if (!isIndexExists('customer_contacts', 'customer_contacts_customer_id_index')) {
				
				$table->index('customer_id');
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
		Schema::table('customer_contacts', function(Blueprint $table)
		{
			$table->dropIndex('customer_contacts_customer_id_index');
		});
	}

}
