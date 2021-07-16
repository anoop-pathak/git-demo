<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTasksAddReminderTypeAndReminderFrequencyColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tasks', function(Blueprint $table) {
			$table->string('reminder_type')->nullable();
			$table->string('reminder_frequency')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tasks', function(Blueprint $table) {
			$table->dropColumn('reminder_type');
			$table->dropColumn('reminder_frequency');
		});
	}

}
