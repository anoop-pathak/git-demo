<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeasurementsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('measurements', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->foreign('company_id')->references('id')->on('companies');
			$table->integer('job_id')->unsigned();
			$table->foreign('job_id')->references('id')->on('jobs');
			$table->string('title');
			$table->string('thumb')->nullable();
			$table->string('image')->nullable();
			$table->boolean('is_file')->default(false);
			$table->string('file_name')->nullable();
			$table->string('file_path')->nullable();
			$table->string('file_mime_type')->nullable();
			$table->double('file_size')->nullable();
			$table->integer('ev_report_id')->nullable()->index();
			$table->integer('ev_file_type_id')->nullable()->index();
			$table->string('sm_order_id')->nullable();
			$table->foreign('sm_order_id')->references('order_id')->on('sm_orders');
			$table->integer('created_by')->unsigned();
			$table->foreign('created_by')->references('id')->on('users');
			$table->integer('deleted_by')->unsigned()->nullable();
			$table->foreign('deleted_by')->references('id')->on('users');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('measurements');
	}

}
