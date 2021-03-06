<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddUserFavouriteEntitiesNameFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_favourite_entities', function(Blueprint $table)
		{
			$table->string('name')->after('id')->nullable();
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
			$table->dropColumn('name');
		});
	}

}
