<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentsAddJpToGoogleSyncField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE appointments MODIFY COLUMN occurence INT NULL");

		Schema::table('appointments', function(Blueprint $table)
		{
			$table->boolean('jp_to_google_sync')->default(true);
		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE appointments MODIFY COLUMN occurence INT NOT NULL DEFAULT 0");
		Schema::table('appointments', function(Blueprint $table)
		{
			$table->dropColumn('jp_to_google_sync');
		});
	}

}
