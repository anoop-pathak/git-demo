<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTaskReminderNotificationTrackAddSentColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('task_reminder_notification_track', function(Blueprint $table) {
			$table->boolean('sent')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('task_reminder_notification_track', function(Blueprint $table) {
			$table->dropColumn('sent');
		});
	}

}
