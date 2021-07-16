<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobSchedulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::dropIfExists('job_schedules');
		Schema::create('job_schedules', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('job_id');
			$table->string('title');
			$table->text('description')->nullable();
			$table->dateTime('start_date_time');
			$table->dateTime('end_date_time');
			$table->integer('created_by')->nullable();
			$table->integer('modified_by')->nullable();
			$table->string('google_event_id')->nullable();
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
		Schema::drop('job_schedules');
	}

}
