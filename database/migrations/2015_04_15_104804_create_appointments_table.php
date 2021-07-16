<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppointmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('appointments', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('user_id');
			$table->integer('customer_id');
			$table->integer('job_id')->nullable();
			$table->string('title');
			$table->text('description');
			$table->dateTime('start_date_time');
			$table->dateTime('end_date_time');
			$table->string('location');
			$table->float('lat', 10, 6)->nullable();
		    $table->float('long', 10, 6)->nullable();
			$table->string('google_event_id');
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
		Schema::drop('appointments');
	}

}
