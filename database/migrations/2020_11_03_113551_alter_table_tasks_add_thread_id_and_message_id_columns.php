<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTasksAddThreadIdAndMessageIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function(Blueprint $table) {
			$table->string('thread_id')->index()->nullable();
			$table->integer('message_id')->index()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tasks', function(Blueprint $table) {
			$table->dropColumn('message_id');
			$table->dropColumn('thread_id');
		});
	}

}
