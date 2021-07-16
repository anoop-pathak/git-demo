<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmReportsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sm_report_files', function(Blueprint $table) 
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->string('order_id')->index()->comment('Skymeasure Order ID');
			$table->string('file_id')->comment('Skymeasure file id');
			$table->string('name');
			$table->string('path');
			$table->string('mime_type');
			$table->string('size');
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
		Schema::drop('sm_report_files');
	}

}
