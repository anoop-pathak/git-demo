<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessageMediaAddShortUrl extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_message_media', function(Blueprint $table)
		{
			$table->string('short_url')->after('media_url')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phone_message_media', function(Blueprint $table) {
			$table->dropColumn('short_url');
		});
	}

}
