<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookSyncCustomersMakeNullableQbIdCustomerId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE quickbook_sync_customers MODIFY qb_id INT NULL");
		DB::statement("ALTER TABLE quickbook_sync_customers MODIFY customer_id INT NULL");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE quickbook_sync_customers MODIFY qb_id INT NOT NULL");
		DB::statement("ALTER TABLE quickbook_sync_customers MODIFY customer_id INT NOT NULL");
	}

}
