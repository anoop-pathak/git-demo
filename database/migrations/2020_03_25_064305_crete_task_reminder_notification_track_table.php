<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreteTaskReminderNotificationTrackTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('task_reminder_notification_track', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('task_id');
			$table->text('user_ids');
			$table->string('setting');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('task_reminder_notification_track');
	}

}
