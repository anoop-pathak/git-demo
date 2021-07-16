<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoverJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hover_jobs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('job_id')->index();
			$table->integer('hover_job_id')->index();
			$table->integer('company_id')->index();
			$table->integer('deliverable_id')->default(4);
			$table->integer('client_id');
			$table->string('state')->default('uploading');
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
		Schema::drop('hover_jobs');
	}

}
