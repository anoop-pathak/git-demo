<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFollowUpAddAppointmentIdOrderField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_follow_up', function($table){
			$table->integer('order')->default(0);
			$table->integer('appointment_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_follow_up', function($table){
			$table->dropColumn('order');
			$table->dropColumn('appointment_id');
		});
	}

}
