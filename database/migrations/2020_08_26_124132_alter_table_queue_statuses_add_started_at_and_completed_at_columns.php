<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQueueStatusesAddStartedAtAndCompletedAtColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('queue_statuses', function(Blueprint $table) {
			$table->dateTime('queue_started_at')->nullable();
			$table->dateTime('queue_completed_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('queue_statuses', function(Blueprint $table) {
			$table->dropColumn('queue_completed_at');
			$table->dropColumn('queue_started_at');
		});
	}

}
