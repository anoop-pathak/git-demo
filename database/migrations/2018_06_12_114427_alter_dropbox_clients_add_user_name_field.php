<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterDropboxClientsAddUserNameField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('dropbox_clients', function(Blueprint $table)
		{
			$table->string('user_name')->after('user_id');
			
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('dropbox_clients', function(Blueprint $table) {
			$table->dropColumn('user_name');
		});
	}

}
