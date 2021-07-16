<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessagesAddMessageIdAndThreadIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_messages', function(Blueprint $table)
		{
			$table->integer('message_id')->nullable();
			$table->string('message_thread_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phone_messages', function(Blueprint $table) {
			$table->dropColumn('message_id');
			$table->dropColumn('message_thread_id');
		});
	}

}
