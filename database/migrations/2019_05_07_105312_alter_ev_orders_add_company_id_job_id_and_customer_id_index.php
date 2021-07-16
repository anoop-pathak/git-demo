<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvOrdersAddCompanyIdJobIdAndCustomerIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_orders', function(Blueprint $table) {
			if (!isIndexExists('ev_orders', 'ev_orders_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('ev_orders', 'ev_orders_job_id_index')) {
				$table->index('job_id');
			}
			if (!isIndexExists('ev_orders', 'ev_orders_customer_id_index')) {
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
		Schema::table('ev_orders', function(Blueprint $table) {
			$table->dropIndex('ev_orders_company_id_index');
			$table->dropIndex('ev_orders_job_id_index');
			$table->dropIndex('ev_orders_customer_id_index');
		});
	}

}
