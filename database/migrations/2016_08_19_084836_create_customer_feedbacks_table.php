<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerFeedbacksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customer_feedbacks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('job_id');
			$table->integer('customer_id');
			$table->string('type');
			$table->text('subject');
			$table->text('description');
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
		Schema::drop('customer_feedbacks');
	}

}
