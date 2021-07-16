<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessageMediaAddResourceIdAndType extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_message_media', function(Blueprint $table)
		{
			$table->integer('resource_id')->after('company_id')->index();
			$table->string('type')->after('media_url')->nullable();
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
			$table->dropColumn('resource_id');
			$table->dropColumn('type');
		});
	}

}
