<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateTableAppointmentReminders extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::create('appointment_reminders', function(Blueprint $table) {
 			$table->increments('id');
 			$table->integer('appointment_id');
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
 		Schema::drop('appointment_reminders');
 	}
 }