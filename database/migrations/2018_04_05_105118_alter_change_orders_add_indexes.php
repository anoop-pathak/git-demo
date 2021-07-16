<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrdersAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_orders', function($table)
		{
			if (!isIndexExists('change_orders', 'change_orders_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('change_orders', 'change_orders_job_id_index')) {
				
				$table->index('job_id');
			}
		});

		Schema::table('change_order_entities', function($table)
		{
			if (!isIndexExists('change_order_entities', 'change_order_entities_change_order_id_index')) {
				
				$table->index('change_order_id');
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
		Schema::table('change_orders', function($table)
		{
			$table->dropIndex('change_orders_company_id_index');
			$table->dropIndex('change_orders_job_id_index');
		});

		Schema::table('change_order_entities', function($table)
		{
			$table->dropIndex('change_order_entities_change_order_id_index');
		});
	}

}
