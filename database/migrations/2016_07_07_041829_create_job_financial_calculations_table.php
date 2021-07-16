<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobFinancialCalculationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_financial_calculations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('job_id');
			$table->float('total_job_amount');
			$table->float('total_change_order_amount');
			$table->float('total_amount');
			$table->float('total_received_payemnt');
			$table->float('pending_payment');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_financial_calculations');
	}

}
