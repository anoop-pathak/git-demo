<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGoogleClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('google_clients', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('user_id');
			$table->string('email');
			$table->text('token');
			$table->string('calender_id')->nullable();
			$table->string('channel_id')->nullable();
			$table->bigInteger('channel_expire_time')->nullable();
			$table->string('resource_id')->nullable();
			$table->string('next_sync_token')->nullable();
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
		Schema::drop('google_clients');
	}

}
