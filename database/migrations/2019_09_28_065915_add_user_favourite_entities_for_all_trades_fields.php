<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddUserFavouriteEntitiesForAllTradesFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_favourite_entities', function(Blueprint $table)
		{
			$table->boolean('for_all_trades')->default(true);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_favourite_entities', function(Blueprint $table)
		{
			$table->dropColumn('for_all_trades');
		});
	}

}
