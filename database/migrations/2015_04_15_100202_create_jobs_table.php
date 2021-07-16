<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('jobs', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('workflow_id');
			$table->string('name');
			$table->string('number');
			$table->integer('customer_id');
			$table->integer('job_type_id')->nullable();
			$table->text('description')->nullable();
			$table->integer('address_id');
			$table->boolean('same_as_customer_address')->default(false);
			$table->integer('created_by');
			$table->integer('last_modified_by');
			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
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
		Schema::drop('jobs');
	}

}
