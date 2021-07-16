<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HoverCaptureRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hover_capture_requests', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('hover_job_id');
			$table->integer('capture_request_id');
			$table->integer('deliverable_id');
			$table->string('name');
			$table->string('email');
			$table->string('phone');
			$table->string('current_user_email');
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
		Schema::drop('hover_capture_requests');
	}

}
