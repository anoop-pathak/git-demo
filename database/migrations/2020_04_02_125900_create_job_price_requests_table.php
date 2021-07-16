<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobPriceRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_price_requests', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('job_id')->index();
			$table->float('amount');
			$table->integer('custom_tax_id')->nullable();
			$table->float('tax_rate')->nullable();
			$table->boolean('taxable')->nullable();
			$table->integer('requested_by')->index();
			$table->integer('approved_by')->nullable();
			$table->integer('rejected_by')->nullable();
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
		Schema::drop('job_price_requests');
	}

}
