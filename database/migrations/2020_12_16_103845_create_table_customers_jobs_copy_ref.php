<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCustomersJobsCopyRef extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customers_and_jobs_copy_ref', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('ref_type')->index();
			$table->integer('from_company_id')->index();
			$table->integer('from_ref_id')->index();
			$table->integer('to_company_id')->index();
			$table->integer('to_ref_id')->index();
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
		Schema::drop('customers_and_jobs_copy_ref');
	}

}
