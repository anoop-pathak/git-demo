<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGoogleClientsAddScopeFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('google_clients', function($table) {

			$table->boolean('scope_calendar_and_tasks')->default(false);
			$table->boolean('scope_drive')->default(false);
			$table->boolean('scope_google_sheet')->default(false);
		});

		DB::statement('UPDATE google_clients SET scope_calendar_and_tasks=1 WHERE user_id IS NOT NULL');
		DB::statement('UPDATE google_clients SET scope_google_sheet=1 WHERE user_id IS NULL');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('google_clients', function($table)
		{
			$table->dropColumn('scope_calendar_and_tasks');
			$table->dropColumn('scope_drive');
			$table->dropColumn('scope_google_sheet');
		});
	}

}
