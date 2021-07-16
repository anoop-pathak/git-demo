<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentsAddOrginialDateTimeFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointments', function($table){
			$table->dateTime('start_date_time_original')->nullable();
			$table->dateTime('end_date_time_original')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointments', function($table){
			$table->dropColumn('start_date_time_original');
			$table->dropColumn('end_date_time_original');
		});
	}

}
