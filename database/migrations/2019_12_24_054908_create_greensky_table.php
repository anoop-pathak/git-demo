<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGreenskyTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('greensky', function(Blueprint $table) 
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('job_id');
			$table->integer('customer_id');
			$table->integer('application_id');
			$table->string('status')->nullable();
			$table->text('meta')->nullable();
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
		Schema::drop('greensky');
	}

}
