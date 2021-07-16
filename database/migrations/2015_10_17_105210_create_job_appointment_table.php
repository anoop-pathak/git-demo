<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobAppointmentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_appointment', function($table){
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('appointment_id')->unsigned()->index();
			$table->integer('job_id')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_appointment');
	}

}
