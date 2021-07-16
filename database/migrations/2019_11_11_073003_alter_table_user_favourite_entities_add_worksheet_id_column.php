<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableUserFavouriteEntitiesAddWorksheetIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('user_favourite_entities', function(Blueprint $table) {
			$table->integer('worksheet_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('user_favourite_entities', function(Blueprint $table) {
			$table->dropColumn('worksheet_id');
		});
	}

}
