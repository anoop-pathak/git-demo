<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableJobPaymentLines extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_payment_lines', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->string('job_payment_id');
			$table->integer('jp_id');
			$table->string('customer_id');
			$table->integer('company_id');
			$table->string('line_type');
			$table->integer('quickbook_id');
			$table->float('amount');
			$table->tinyInteger('origin')->default(0); 
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
		Schema::drop('job_payment_lines');
	}

}