<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobCustomFieldsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_custom_fields', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('job_id')->unsigned();
			$table->foreign('job_id')->references('id')->on('jobs');
			$table->string('name');
			$table->string('value');
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
		Schema::drop('job_custom_fields');
	}

}
