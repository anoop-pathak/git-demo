<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTimelogsTableUpdateDuration extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("UPDATE timelogs SET duration = TIMESTAMPDIFF(SECOND, `start_date_time`, `end_date_time`) WHERE end_date_time IS NOT NULL;");
		DB::statement("ALTER TABLE `timelogs` CHANGE `duration` `duration` VARCHAR(255) DEFAULT NULL COMMENT 'duration in seconds';");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		
	}

}
