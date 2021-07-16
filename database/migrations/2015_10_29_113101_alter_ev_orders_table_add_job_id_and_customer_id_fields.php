<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvOrdersTableAddJobIdAndCustomerIdFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_orders', function($table){
			$table->integer('job_id');
			$table->integer('customer_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ev_orders', function($table){
			$table->dropColumn('job_id');
			$table->dropColumn('customer_id');
		});
	}

}
