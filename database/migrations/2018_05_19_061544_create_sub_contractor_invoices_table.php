<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubContractorInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sub_contractor_invoices', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('user_id');
			$table->integer('job_id');
			$table->integer('job_schedule_id');
			$table->string('file_name');
			$table->string('file_path');
			$table->string('thumb');
			$table->string('mime_type');
			$table->double('size');
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
		Schema::drop('sub_contractor_invoices');
	}

}
