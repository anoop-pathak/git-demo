<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTimelogsAddCheckInLocationAndCheckOutLocationField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('timelogs', function(Blueprint $table)
		{
			$table->string('check_out_location')->nullable()->after('location');
			$table->renameColumn('location', 'check_in_location');
		});
		DB::statement("UPDATE timelogs SET check_out_location = timelogs.check_in_location");
		DB::statement("ALTER TABLE timelogs MODIFY job_id INT NULL");
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
			$table->dropColumn('check_out_location');
			$table->renameColumn('check_in_location', 'location');
		});
	}

}
