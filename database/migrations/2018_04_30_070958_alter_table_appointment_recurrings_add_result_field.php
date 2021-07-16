<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAppointmentRecurringsAddResultField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_recurrings', function($table) {
			$table->string('result')->nullable();
			$table->text('result_text1')->nullable();
			$table->text('result_text2')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_recurrings', function($table) {
			$table->dropColumn('result');
			$table->dropColumn('result_text1');
			$table->dropColumn('result_text2');
		});
	}

}
