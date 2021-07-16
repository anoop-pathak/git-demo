<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGoogleClientAddScopeGmailField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('google_clients', function(Blueprint $table) {
			$table->boolean('scope_gmail')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('google_clients', function(Blueprint $table) {
			$table->dropColumn('scope_gmail');
		});
	}

}
