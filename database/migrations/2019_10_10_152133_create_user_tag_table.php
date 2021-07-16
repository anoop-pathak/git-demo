<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTagTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_tag', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('tag_id')->unsigned()->index();
			$table->integer('user_id')->unsigned()->index();
			$table->integer('company_id')->unsigned()->index();
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
		Schema::drop('user_tag');
	}

}
