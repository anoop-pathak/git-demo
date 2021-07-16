<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserProfileTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_profile', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('user_id')->unique();
			$table->string('phone')->nullable();
			$table->string('cell')->nullable();
			$table->string('address');
			$table->string('address_line_1')->nullable();
			$table->string('city');
			$table->integer('state_id');
			$table->string('country_id');
			$table->string('zip');
			$table->string('position');
			$table->string('profile_pic');
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
		Schema::drop('user_profile');
	}

}
