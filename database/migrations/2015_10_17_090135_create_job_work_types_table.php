<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobWorkTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_work_types', function($table){
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('job_id')->unsingned()->index();
			$table->integer('job_type_id')->unsingned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_work_types');
	}

}
