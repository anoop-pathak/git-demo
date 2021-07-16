<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimelogsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('timelogs', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->unsigned()->index();
			$table->integer('user_id')->unsigned()->index();
			$table->integer('job_id')->index();
			$table->string('duration')->nullable()->comment("duration in hours");
			$table->dateTime('start_date_time')->index();
			$table->dateTime('end_date_time')->index()->nullable();
			$table->string('location')->nullable();
			$table->string('check_in_image')->nullable();
			$table->string('check_out_image')->nullable();
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
		Schema::drop('timelogs');
	}

}
