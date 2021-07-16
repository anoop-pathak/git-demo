<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserFavouriteEntityTrades extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_favourite_entity_trades', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_favourite_entity_id')->unsigned()->index();
			$table->integer('trade_id')->unsigned()->index();
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
		Schema::drop('user_favourite_entity_trades');
	}

}
