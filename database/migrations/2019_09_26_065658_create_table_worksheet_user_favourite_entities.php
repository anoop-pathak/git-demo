<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTableWorksheetUserFavouriteEntities extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_favourite_entities', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('type');
			$table->integer('entity_id');
			$table->integer('marked_by');
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
		Schema::drop('user_favourite_entities');
	}

}
