<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoverReportsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hover_reports', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('hover_job_id')->index();
			$table->string('file_path')->nullable();
			$table->string('file_name')->nullable();
   			$table->string('file_mime_type')->nullable();
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
		Schema::drop('hover_reports');
	}

}
