<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailBounceSummaryTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `email_bounce_summary` MODIFY `status` varchar(255) NULL;');
		DB::statement('ALTER TABLE `email_bounce_summary` MODIFY `reason` text NULL;');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `email_bounce_summary` MODIFY `status` varchar(255) NOT NULL;');
		DB::statement('ALTER TABLE `email_bounce_summary` MODIFY `reason` text NOT NULL;');
	}

}
