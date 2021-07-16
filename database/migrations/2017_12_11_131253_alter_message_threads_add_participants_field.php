<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMessageThreadsAddParticipantsField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_threads', function(Blueprint $table)
		{
			$table->string('participant')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_threads', function(Blueprint $table)
		{
			$table->dropColumn('participant');
		});
	}

}
