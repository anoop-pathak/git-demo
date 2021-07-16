<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMobileAppsAddUrlField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('mobile_apps', function(Blueprint $table)
		{
			$table->string('url')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('mobile_apps', function(Blueprint $table)
		{
			$table->dropColumn('url');
		});
	}

}
