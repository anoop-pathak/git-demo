<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNewResourcesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('UPDATE new_resources SET thumb_exists = 1 WHERE mime_type IN ("image/jpeg","image/jpg","image/png")');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('UPDATE new_resources SET thumb_exists = 0 WHERE mime_type IN ("image/jpeg","image/jpg","image/png")');
	}

}
