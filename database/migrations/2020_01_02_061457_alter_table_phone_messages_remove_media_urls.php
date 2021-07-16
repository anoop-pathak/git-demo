<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessagesRemoveMediaUrls extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_messages', function(Blueprint $table) {
			$table->dropColumn('media_urls');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phone_messages', function(Blueprint $table)
		{
			$table->string('media_urls')->nullable();
		});
	}

}
