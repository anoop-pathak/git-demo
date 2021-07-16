<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempLogsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('temp_logs', function(Blueprint $table) 
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->nullable();
			$table->string('key');
			$table->string('value')->nullable();
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
		Schema::drop('temp_logs');
	}

}
