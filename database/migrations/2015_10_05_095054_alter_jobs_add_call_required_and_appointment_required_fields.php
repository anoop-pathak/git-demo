<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddCallRequiredAndAppointmentRequiredFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs',function($table){
			$table->boolean('call_required')->default(false);
			$table->boolean('appointment_required')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('jobs',function($table){
			$table->dropColumn('call_required');
			$table->dropColumn('appointment_required');
		});
	}

}
