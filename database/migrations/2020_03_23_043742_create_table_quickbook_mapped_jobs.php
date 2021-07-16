<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableQuickbookMappedJobs extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_mapped_jobs', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('batch_id')->index();
			$table->integer('qb_customer_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('job_id')->nullable();
			$table->integer('qb_job_id')->nullable();
			$table->integer('created_by')->nullable();
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
		Schema::dropIfExists('quickbook_mapped_jobs');
	}

}
