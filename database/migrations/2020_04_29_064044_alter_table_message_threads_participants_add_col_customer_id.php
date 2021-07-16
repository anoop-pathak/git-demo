<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageThreadsParticipantsAddColCustomerId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_thread_participants', function(Blueprint $table) {
			$table->integer('customer_id')->nullable()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_thread_participants', function(Blueprint $table){
			$table->dropColumn('customer_id');
		});
	}

}
