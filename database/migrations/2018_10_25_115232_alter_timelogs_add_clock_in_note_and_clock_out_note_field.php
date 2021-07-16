<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTimelogsAddClockInNoteAndClockOutNoteField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('timelogs', function(Blueprint $table)
		{
			$table->text('clock_in_note')->nullable()->after('start_date_time');
			$table->text('clock_out_note')->nullable()->after('end_date_time');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('timelogs', function(Blueprint $table)
		{
			$table->dropColumn('clock_in_note');
			$table->dropColumn('clock_out_note');
		});
	}

}
