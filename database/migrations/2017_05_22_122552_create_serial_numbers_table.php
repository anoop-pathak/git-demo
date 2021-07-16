<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSerialNumbersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('serial_numbers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('type');
			$table->integer('start_from')->default(false);
			$table->integer('current')->default(false);
			$table->boolean('is_active')->default(false);
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
		Schema::drop('serial_numbers');
	}

}
