<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobInsuranceTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_insurance_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('job_id');
			$table->string('insurance_company')->nullable();
			$table->string('insurance_number')->nullable();
			$table->string('phone')->nullable();
			$table->string('fax')->nullable();
			$table->string('email')->nullable();
			$table->string('adjuster_name')->nullable();
			$table->string('adjuster_phone')->nullable();
			$table->string('adjuster_email')->nullable();
			$table->string('rcv')->nullable();
			$table->string('deductable_amount')->nullable();
			$table->string('policy_number')->nullable();
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
		Schema::drop('job_insurance_details');
	}

}
