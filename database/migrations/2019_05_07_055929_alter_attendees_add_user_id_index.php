<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAttendeesAddUserIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('attendees', function(Blueprint $table) {
			if (!isIndexExists('attendees', 'attendees_user_id_index')) {
				$table->index('user_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('attendees', function(Blueprint $table) {
			$table->dropIndex('attendees_user_id_index');
		});
	}

}
