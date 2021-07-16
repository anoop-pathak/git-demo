<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobSchedulesAddCustomerIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_schedules', function(Blueprint $table)
		{
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
		Schema::table('job_schedules', function(Blueprint $table)
		{
			$table->dropColumn('customer_id');
		});
	}

}
