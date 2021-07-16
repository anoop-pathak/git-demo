<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMesssageMediaRemoveResourceId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_message_media', function(Blueprint $table) {
			$table->dropColumn('resource_id');
			$table->dropColumn('type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phone_message_media', function(Blueprint $table)
		{
			$table->integer('resource_id')->nullable();
			$table->string('type')->nullable();
		});
	}

}
