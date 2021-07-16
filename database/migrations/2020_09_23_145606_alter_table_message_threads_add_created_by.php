<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageThreadsAddCreatedBy extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_threads', function(Blueprint $table)
		{
			$table->integer('created_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_threads', function(Blueprint $table) {
			$table->dropColumn('created_by');
		});
	}
}
