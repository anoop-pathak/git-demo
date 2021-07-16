<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateTableScheduleReminders extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::create('schedule_reminders', function(Blueprint $table) {
 			$table->increments('id');
 			$table->integer('schedule_id');
 			$table->string('type');
 			$table->string('minutes');
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
 		Schema::drop('schedule_reminders');
 	}
 }
