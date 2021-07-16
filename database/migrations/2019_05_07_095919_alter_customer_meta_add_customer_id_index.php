<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomerMetaAddCustomerIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customer_meta', function(Blueprint $table) {
			if (!isIndexExists('customer_meta', 'customer_meta_customer_id_index')) {
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
		Schema::table('customer_meta', function(Blueprint $table) {
			$table->dropIndex('customer_meta_customer_id_index');
		});
	}

}
