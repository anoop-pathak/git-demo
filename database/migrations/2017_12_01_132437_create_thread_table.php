<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateThreadTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('message_threads', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->string('id')->unique();
			$table->integer('company_id')->unsigned()->index();
			$table->integer('job_id')->nullable()->unsigned()->index();
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
		Schema::drop('message_threads');
	}

}
